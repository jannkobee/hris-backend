<?php

namespace App\Services\LeaveAccrual;

use App\Models\LeaveCreditSetting;
use App\Models\ScheduledTask;

class LeaveAccrualScheduleSyncer
{
    public const TASK_NAME = 'Leave Accrual';

    public function sync(): void
    {
        $hasActiveSettings = LeaveCreditSetting::query()->where('is_active', true)->exists();

        ScheduledTask::updateOrCreate(
            ['name' => self::TASK_NAME],
            [
                'description' => 'Accrues leave credits from active Leave Credit Settings. Runs daily to catch new employees and missed runs; the ledger prevents duplicate credits.',
                'command' => 'leave-credits:accrue',
                'frequency' => 'daily',
                'run_time' => '01:00',
                'run_day_of_month' => null,
                'run_days' => null,
                'run_months' => null,
                'cron_expression' => null,
                'is_active' => $hasActiveSettings,
            ],
        );
    }
}
