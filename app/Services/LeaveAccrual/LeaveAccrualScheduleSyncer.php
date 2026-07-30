<?php

namespace App\Services\LeaveAccrual;

use App\Models\LeaveCreditSetting;
use App\Models\ScheduledTask;

/**
 * Keeps the "Leave Accrual" row in `scheduled_tasks` truthful about how
 * often `leave-credits:accrue` actually needs to run, based on the active
 * rules in Leave Credit Settings — so the two screens can never drift out
 * of sync with each other.
 *
 * Leave Credit Settings owns *what* accrues (per leave type, per amount,
 * per its own frequency/run_months). This service only asks: "given all of
 * that, what's the tightest interval the command must run at so nothing
 * gets missed?" and writes that single answer into the scheduled task.
 */
class LeaveAccrualScheduleSyncer
{
    public const TASK_NAME = 'Leave Accrual';

    // Most frequent first — the accrue command must run at least this often
    // or a tighter active rule (e.g. weekly) would never get checked.
    private const FREQUENCY_RANK = [
        'daily' => 0,
        'weekly' => 1,
        'monthly' => 2,
        'yearly' => 3,
    ];

    public function sync(): void
    {
        $activeFrequencies = LeaveCreditSetting::query()
            ->where('is_active', true)
            ->pluck('frequency')
            ->filter(fn($frequency) => isset(self::FREQUENCY_RANK[$frequency]));

        $hasActiveSettings = $activeFrequencies->isNotEmpty();

        $requiredFrequency = $activeFrequencies
            ->sortBy(fn($frequency) => self::FREQUENCY_RANK[$frequency])
            ->first() ?? 'monthly';

        ScheduledTask::updateOrCreate(
            ['name' => self::TASK_NAME],
            [
                'description' => 'Accrues leave credits per the active rules in Leave Credit Settings. '
                    . 'Frequency here is auto-derived — edit rules in Leave Credit Settings, not here.',
                'command' => 'leave-credits:accrue',
                'frequency' => $requiredFrequency,
                'run_time' => '01:00',
                'run_day_of_month' => 1,
                // No active leave-type rules means nothing to accrue —
                // pause the job rather than running it for nothing.
                'is_active' => $hasActiveSettings,
            ],
        );
    }
}
