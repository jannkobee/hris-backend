<?php

namespace App\Repository\LeaveRequest;

use App\Models\LeaveRequest;
use App\Models\LeaveCredit;
use App\Repository\Base\BaseRepository;
use App\Services\AuditLog\AuditLogServiceInterface;
use App\Services\Utils\ResponseServiceInterface;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class LeaveRequestRepository extends BaseRepository implements LeaveRequestRepositoryInterface
{
    public function __construct(LeaveRequest $model, ResponseServiceInterface $responseService, AuditLogServiceInterface $auditLogService)
    {
        parent::__construct($model, $responseService, $auditLogService);
    }

    public function create(array $attributes): JsonResponse
    {
        $startDate = Carbon::parse($attributes['start_date']);
        $endDate = Carbon::parse($attributes['end_date']);

        $requestedDays = $startDate->diffInDays($endDate) + 1;

        $credit = LeaveCredit::where('employee_id', $attributes['employee_id'])
            ->where('leave_type_id', $attributes['leave_type_id'])
            ->where('year', $startDate->year)
            ->first();

        if (!$credit) {
            return $this->responseService->resolveResponse(
                "No leave credits found for this category in {$startDate->year}.",
                null,
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $remaining = $credit->total_earned - $credit->used;
        if ($remaining < $requestedDays) {
            return $this->responseService->resolveResponse(
                "Insufficient leave balance. Available: {$remaining} days.",
                null,
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $attributes['status'] = 'pending';
        $created = $this->model->create($attributes)->fresh();

        $this->auditLogService->insertLog($this->model, 'create', $attributes);

        return $this->responseService->storeResponse($this->model->model_name, $created);
    }
}
