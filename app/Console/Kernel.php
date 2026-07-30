<?php

namespace App\Console;

use App\Models\ScheduledTask;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();

        // leave-credits:accrue is registered below via scheduleDatabaseTasks().
        // Its frequency is auto-derived from the active rules in Leave
        // Credit Settings (see LeaveAccrualScheduleSyncer) every time those
        // rules change, so it can't drift out of sync with them. Don't
        // hardcode it here.
        $this->scheduleDatabaseTasks($schedule);
    }

    /**
     * Database-driven scheduling.
     *
     * Every row in `scheduled_tasks` with is_active = true is registered here
     * as a real scheduled event. This method runs once per minute (whenever
     * the server's crontab calls `php artisan schedule:run`), so any change
     * made through Scheduled Task Management — a new task, a disabled one,
     * an edited time — takes effect on the very next tick. No deploy, no
     * cache clear, no touching the server's crontab.
     *
     * Server crontab (set up once, never touched again):
     *   * * * * * cd /path-to-your-app && php artisan schedule:run >> /dev/null 2>&1
     */
    private function scheduleDatabaseTasks(Schedule $schedule): void
    {
        ScheduledTask::query()->where('is_active', true)->get()->each(function (ScheduledTask $task) use ($schedule) {
            $event = $schedule->command($task->command)
                ->name($task->name)
                ->timezone($task->timezone ?? config('app.timezone'))
                ->onSuccess(function () use ($task) {
                    $task->update(['last_run_at' => now(), 'last_run_output' => 'Success']);
                })
                ->onFailure(function () use ($task) {
                    $task->update(['last_run_at' => now(), 'last_run_output' => 'Failed']);
                });

            $time = $task->run_time ?? '00:00';

            match ($task->frequency) {
                'daily' => $event->dailyAt($time),
                'weekly' => $event->weeklyOn($task->run_days ?? [1], $time),
                'monthly' => $event->monthlyOn($task->run_day_of_month ?? 1, $time),
                'yearly' => $event->yearlyOn(
                    1,
                    $task->run_day_of_month ?? 1,
                    $time,
                )->months($task->run_months ?? [1]),
                default => $event->cron($task->cron_expression ?? '* * * * *'), // 'custom'
            };
        });
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
