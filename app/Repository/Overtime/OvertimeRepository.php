<?php

namespace App\Repository\Overtime;

use App\Models\Overtime;
use App\Repository\Base\BaseRepository;
use App\Services\AuditLog\AuditLogServiceInterface;
use App\Services\Utils\ResponseServiceInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class OvertimeRepository extends BaseRepository implements OvertimeRepositoryInterface
{
    public function __construct(
        Overtime $model,
        ResponseServiceInterface $responseService,
        AuditLogServiceInterface $auditLogService
    ) {
        parent::__construct($model, $responseService, $auditLogService);
    }

    protected function applyVisibilityScope(Builder $query): Builder
    {
        return $this->canManageAll()
            ? $query
            : $query->where('employee_id', $this->currentEmployeeId());
    }

    public function create(array $attributes): JsonResponse
    {
        $this->ensureCanActForEmployee($attributes['employee_id']);
        $attributes['status'] = 'pending';

        return parent::create($attributes);
    }

    public function find(string $id): JsonResponse
    {
        $overtime = $this->findOvertime($id);
        $this->ensureCanActForEmployee($overtime->employee_id);

        return parent::find($id);
    }

    public function update(array $attributes, string|int $id): JsonResponse
    {
        $overtime = $this->findOvertime((string) $id);
        $this->ensureCanActForEmployee($overtime->employee_id);

        if ($overtime->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => 'Only pending overtime requests can be updated.',
            ]);
        }

        return parent::update($attributes, $id);
    }

    public function delete(string $id): JsonResponse
    {
        $overtime = $this->findOvertime($id);
        $this->ensureCanActForEmployee($overtime->employee_id);

        if ($overtime->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => 'Only pending overtime requests can be removed.',
            ]);
        }

        return parent::delete($id);
    }

    public function approve(string $id, ?string $remarks = null): JsonResponse
    {
        return $this->setStatus($id, 'approved', $remarks);
    }

    public function reject(string $id, ?string $remarks = null): JsonResponse
    {
        return $this->setStatus($id, 'rejected', $remarks);
    }

    /**
     * Shared logic for approve/reject: both just flip status, stamp the
     * approver/timestamp, and log the action the same way.
     */
    protected function setStatus(string $id, string $status, ?string $remarks): JsonResponse
    {
        if (! Auth::user()?->hasPermission('approve-overtimes')) {
            throw new AuthorizationException('You do not have permission to approve overtime requests.');
        }

        $overtime = $this->findOvertime($id);

        if ($overtime->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => 'Only pending overtime requests can be actioned.',
            ]);
        }

        $attributes = [
            'status' => $status,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'remarks' => $remarks,
        ];

        $overtime->update($attributes);
        $overtime->load(['employee', 'approver']);

        $this->auditLogService->insertLog($this->model, $status, $attributes);

        return $this->responseService->updateResponse(
            $this->model->model_name,
            $overtime
        );
    }

    private function ensureCanActForEmployee(string $employeeId): void
    {
        if (! $this->canManageAll() && $this->currentEmployeeId() !== $employeeId) {
            throw new AuthorizationException('You can only access your own overtime requests.');
        }
    }

    private function canManageAll(): bool
    {
        $user = Auth::user();

        return (bool) ($user?->hasPermission('manage-overtimes')
            || $user?->hasPermission('approve-overtimes'));
    }

    private function currentEmployeeId(): string
    {
        $employeeId = Auth::user()?->employee?->id;

        if (! $employeeId) {
            throw new AuthorizationException('This account is not linked to an employee record.');
        }

        return $employeeId;
    }

    private function findOvertime(string $id): Overtime
    {
        $overtime = $this->model->find($id);

        if (! $overtime) {
            throw ValidationException::withMessages(['record_not_found' => 'Record not found.']);
        }

        return $overtime;
    }
}
