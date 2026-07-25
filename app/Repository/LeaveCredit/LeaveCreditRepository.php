<?php

namespace App\Repository\LeaveCredit;

use App\Models\LeaveCredit;
use App\Repository\Base\BaseRepository;
use App\Services\AuditLog\AuditLogServiceInterface;
use App\Services\Utils\ResponseServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeaveCreditRepository extends BaseRepository implements LeaveCreditRepositoryInterface
{
    public function __construct(LeaveCredit $model, ResponseServiceInterface $responseService, AuditLogServiceInterface $auditLogService)
    {
        parent::__construct($model, $responseService, $auditLogService);
    }

    public function findOrCreateBucket(string $employeeId, string $leaveTypeId, int $year): LeaveCredit
    {
        return $this->model->newQuery()->firstOrCreate(
            [
                'employee_id' => $employeeId,
                'leave_type_id' => $leaveTypeId,
                'year' => $year,
            ],
            [
                'total_earned' => 0,
                'used' => 0,
            ]
        );
    }

    public function incrementEarned(LeaveCredit $credit, float $amount): LeaveCredit
    {
        $credit->increment('total_earned', $amount);

        return $credit->refresh();
    }

    /**
     * Overrides BaseRepository::update to make manual balance edits safe:
     *  - locks the row for the duration of the transaction (protects against
     *    a concurrent accrual run or another edit landing mid-update)
     *  - refuses to let "used" exceed "total_earned"
     *  - still writes an audit log entry, same as the base behaviour
     */
    public function update(array $attributes, string|int $id): JsonResponse
    {
        return DB::transaction(function () use ($attributes, $id) {
            $credit = $this->model->newQuery()->lockForUpdate()->find($id);

            if (!$credit) {
                throw ValidationException::withMessages([
                    'record_not_found' => 'Record not found',
                ]);
            }

            $totalEarned = (float) ($attributes['total_earned'] ?? $credit->total_earned);
            $used = (float) ($attributes['used'] ?? $credit->used);

            if ($used > $totalEarned) {
                throw ValidationException::withMessages([
                    'used' => 'Used credits cannot exceed total earned credits.',
                ]);
            }

            $credit->update($attributes);

            $this->auditLogService->insertLog($this->model, 'update', $attributes);

            $relations = request()->input('relations');

            if ($relations) {
                $credit->load(explode(',', $relations));
            }

            return $this->responseService->updateResponse(
                $this->model->model_name,
                $credit
            );
        });
    }
}
