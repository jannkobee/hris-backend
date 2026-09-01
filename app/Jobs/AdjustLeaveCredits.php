<?php

namespace App\Jobs;

use App\Models\LeaveCredit;
use App\Models\LeaveRequest;
use App\Models\Organization;
use App\Tenancy\TenantContext;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AdjustLeaveCredits implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $leaveRequestId;

    public string $organizationId;

    public string $action;

    public function __construct(LeaveRequest $leaveRequest, string $action)
    {
        $this->leaveRequestId = (string) $leaveRequest->getKey();
        $this->organizationId = (string) $leaveRequest->organization_id;
        $this->action = $action;
    }

    public function handle(TenantContext $tenantContext): void
    {
        $organization = Organization::query()
            ->whereKey($this->organizationId)
            ->where('status', Organization::STATUS_ACTIVE)
            ->first();

        if (! $organization) {
            return;
        }

        $tenantContext->run($organization, function (): void {
            $leaveRequest = LeaveRequest::query()->find($this->leaveRequestId);
            if (! $leaveRequest) {
                return;
            }

            $startDate = Carbon::parse($leaveRequest->start_date);
            $endDate = Carbon::parse($leaveRequest->end_date);
            $days = $startDate->diffInDays($endDate) + 1;

            $credit = LeaveCredit::query()
                ->where('employee_id', $leaveRequest->employee_id)
                ->where('leave_type_id', $leaveRequest->leave_type_id)
                ->where('year', $startDate->year)
                ->first();

            if ($credit && $this->action === 'refund') {
                $credit->decrement('used', $days);
            } elseif ($credit && $this->action === 'deduct') {
                $credit->increment('used', $days);
            }
        });
    }
}
