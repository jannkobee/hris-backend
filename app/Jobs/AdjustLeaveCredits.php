<?php

namespace App\Jobs;

use App\Models\LeaveRequest;
use App\Models\LeaveCredit;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AdjustLeaveCredits implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public LeaveRequest $leaveRequest;
    public string $action;

    public function __construct(LeaveRequest $leaveRequest, string $action)
    {
        $this->leaveRequest = $leaveRequest;
        $this->action = $action;
    }

    public function handle(): void
    {
        $startDate = Carbon::parse($this->leaveRequest->start_date);
        $endDate = Carbon::parse($this->leaveRequest->end_date);
        $days = $startDate->diffInDays($endDate) + 1;

        $credit = LeaveCredit::where('employee_id', $this->leaveRequest->employee_id)
            ->where('leave_type_id', $this->leaveRequest->leave_type_id)
            ->where('year', $startDate->year)
            ->first();

        if ($credit) {
            if ($this->action === 'refund') {
                $credit->decrement('used', $days);
            } elseif ($this->action === 'deduct') {
                $credit->increment('used', $days);
            }
        }
    }
}
