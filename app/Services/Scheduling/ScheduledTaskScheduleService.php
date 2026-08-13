<?php

namespace App\Services\Scheduling;

use App\Models\ScheduledTask;
use Carbon\Carbon;
use Cron\CronExpression;

class ScheduledTaskScheduleService
{
    public function cronExpression(ScheduledTask $task): string
    {
        if ($task->frequency === 'custom') {
            return (string) $task->cron_expression;
        }

        [$hour, $minute] = array_pad(explode(':', substr((string) $task->run_time, 0, 5)), 2, '0');
        $hour = (int) $hour;
        $minute = (int) $minute;
        $time = "{$minute} {$hour}";

        return match ($task->frequency) {
            'daily' => "{$time} * * *",
            'weekly' => "{$time} * * ".implode(',', $task->run_days ?: [1]),
            'monthly' => "{$time} ".($task->run_day_of_month ?: 1).' * *',
            'yearly' => "{$time} ".($task->run_day_of_month ?: 1).' '.implode(',', $task->run_months ?: [1]).' *',
            default => '* * * * *',
        };
    }

    public function nextRunAt(ScheduledTask $task): Carbon
    {
        $timezone = $task->timezone ?: config('app.timezone');

        return Carbon::instance(
            CronExpression::factory($this->cronExpression($task))
                ->getNextRunDate(now($timezone), 0, false, $timezone)
        );
    }
}
