<?php

namespace App\Repository\Overtime;

use App\Models\Overtime;
use App\Models\User;
use App\Repository\Base\BaseRepository;
use App\Services\Approvals\DelegatedApproverResolver;
use App\Services\AuditLog\AuditLogServiceInterface;
use App\Services\Overtime\OvertimePolicyEvaluator;
use App\Services\Utils\ResponseServiceInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class OvertimeRepository extends BaseRepository implements OvertimeRepositoryInterface
{
    private DelegatedApproverResolver $delegates;

    private OvertimePolicyEvaluator $policyEvaluator;

    public function __construct(
        Overtime $model,
        ResponseServiceInterface $responseService,
        AuditLogServiceInterface $auditLogService,
        DelegatedApproverResolver $delegates,
        OvertimePolicyEvaluator $policyEvaluator,
    ) {
        parent::__construct($model, $responseService, $auditLogService);
        $this->delegates = $delegates;
        $this->policyEvaluator = $policyEvaluator;
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
        $attributes = [...$attributes, ...$this->policyEvaluator->evaluate($attributes['date'], (float) $attributes['hours'])];

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

        $date = $attributes['date'] ?? $overtime->date->toDateString();
        $hours = $attributes['hours'] ?? $overtime->hours;
        $attributes = [...$attributes, ...$this->policyEvaluator->evaluate($date, (float) $hours)];

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

    public function approve(string $id, string $remarks = null): JsonResponse
    {
        return $this->setStatus($id, 'approved', $remarks);
    }

    public function reject(string $id, string $remarks = null): JsonResponse
    {
        return $this->setStatus($id, 'rejected', $remarks);
    }

    /**
     * Shared logic for approve/reject: both just flip status, stamp the
     * approver/timestamp, and log the action the same way.
     */
    protected function setStatus(string $id, string $status, ?string $remarks): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user || ! $this->delegates->canApprove($user, 'approve-overtimes')) {
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
        /** @var User|null $user */
        $user = Auth::user();

        return (bool) ($user?->hasPermission('manage-overtimes')
            || $user?->hasPermission('approve-overtimes'));
    }

    private function currentEmployeeId(): string
    {
        /** @var User|null $user */
        $user = Auth::user();
        $employeeId = $user?->employee?->id;

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
