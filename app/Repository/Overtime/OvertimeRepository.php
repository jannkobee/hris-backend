<?php

namespace App\Repository\Overtime;

use App\Models\Overtime;
use App\Repository\Base\BaseRepository;
use App\Services\AuditLog\AuditLogServiceInterface;
use App\Services\Utils\ResponseServiceInterface;
use Illuminate\Http\JsonResponse;
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
        $overtime = $this->model->find($id);

        if (!$overtime) {
            throw ValidationException::withMessages([
                'record_not_found' => 'Record not found',
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
}
