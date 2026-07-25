<?php

namespace App\Repository\LeaveConversionRequest;

use App\Models\LeaveConversionRequest;
use App\Repository\Base\BaseRepository;
use App\Repository\LeaveCredit\LeaveCreditRepositoryInterface;
use App\Services\AuditLog\AuditLogServiceInterface;
use App\Services\Utils\ResponseServiceInterface;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeaveConversionRequestRepository extends BaseRepository implements LeaveConversionRequestRepositoryInterface
{
    public function __construct(
        LeaveConversionRequest $model,
        ResponseServiceInterface $responseService,
        AuditLogServiceInterface $auditLogService,
        private readonly LeaveCreditRepositoryInterface $creditRepository,
    ) {
        parent::__construct($model, $responseService, $auditLogService);
    }

    /**
     * Attaches the request to the employee's current-year credit bucket for
     * that leave type, and rejects it up front if there isn't enough balance
     * to even bother submitting. (The balance is re-checked again on approve,
     * since it can move between submission and approval.)
     */
    public function create(array $attributes): JsonResponse
    {
        $year = (int) ($attributes['year'] ?? Carbon::now()->year);

        $credit = $this->creditRepository->findOrCreateBucket(
            $attributes['employee_id'],
            $attributes['leave_type_id'],
            $year,
        );

        if ((float) $attributes['credits_requested'] > $credit->remaining) {
            throw ValidationException::withMessages([
                'credits_requested' => "Requested credits exceed the employee's remaining balance.",
            ]);
        }

        $attributes['leave_credit_id'] = $credit->id;
        $attributes['status'] = 'pending';

        return parent::create($attributes);
    }

    /**
     * Debits the credit bucket and marks the request approved. Locks both
     * rows for the duration of the transaction so a double-approve or a
     * concurrent accrual run can't leave the balance inconsistent.
     */
    public function approve(string $id): JsonResponse
    {
        return DB::transaction(function () use ($id) {
            $conversion = $this->model->newQuery()->lockForUpdate()->find($id);

            if (!$conversion) {
                throw ValidationException::withMessages([
                    'record_not_found' => 'Record not found',
                ]);
            }

            if ($conversion->status !== 'pending') {
                throw ValidationException::withMessages([
                    'status' => 'Only pending conversion requests can be approved.',
                ]);
            }

            $credit = $conversion->leaveCredit()->lockForUpdate()->first();

            if (!$credit || (float) $conversion->credits_requested > $credit->remaining) {
                throw ValidationException::withMessages([
                    'credits_requested' => 'Employee no longer has sufficient remaining credits.',
                ]);
            }

            $credit->increment('used', (float) $conversion->credits_requested);

            $conversion->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            $this->auditLogService->insertLog($this->model, 'approve', ['id' => $id]);

            return $this->responseService->updateResponse(
                $this->model->model_name,
                $conversion->fresh()
            );
        });
    }

    public function reject(string $id, ?string $remarks = null): JsonResponse
    {
        $conversion = $this->model->find($id);

        if (!$conversion) {
            throw ValidationException::withMessages([
                'record_not_found' => 'Record not found',
            ]);
        }

        if ($conversion->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => 'Only pending conversion requests can be rejected.',
            ]);
        }

        $conversion->update([
            'status' => 'rejected',
            'approved_by' => Auth::id(),
            'remarks' => $remarks,
        ]);

        $this->auditLogService->insertLog($this->model, 'reject', ['id' => $id, 'remarks' => $remarks]);

        return $this->responseService->updateResponse(
            $this->model->model_name,
            $conversion->fresh()
        );
    }
}
