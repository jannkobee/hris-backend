<?php

namespace App\Console;

use App\Models\ScheduledTask;
use App\Services\Scheduling\ScheduledTaskScheduleService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Stringable;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $this->scheduleDatabaseTasks($schedule);
    }

    /**
     * Register active database tasks for the next scheduler tick. Configure
     * one server cron entry to run `php artisan schedule:run` every minute.
     */
    private function scheduleDatabaseTasks(Schedule $schedule): void
    {
        $scheduleService = app(ScheduledTaskScheduleService::class);

        ScheduledTask::query()->where('is_active', true)->get()->each(function (ScheduledTask $task) use ($schedule, $scheduleService) {
            $event = $schedule->command($task->command)
                ->name($task->name)
                ->timezone($task->timezone ?: config('app.timezone'))
                ->withoutOverlapping(120)
                ->onSuccess(function (Stringable $output) use ($task, $scheduleService) {
                    $task->update([
                        'last_run_at' => now(),
                        'last_run_output' => trim((string) $output) ?: 'Success',
                        'next_run_at' => $scheduleService->nextRunAt($task),
                    ]);
                })
                ->onFailure(function (Stringable $output) use ($task, $scheduleService) {
                    $task->update([
                        'last_run_at' => now(),
                        'last_run_output' => trim((string) $output) ?: 'Failed',
                        'next_run_at' => $scheduleService->nextRunAt($task),
                    ]);
                });

            $event->cron($scheduleService->cronExpression($task));

            $nextRunAt = $scheduleService->nextRunAt($task);
            if (! $task->next_run_at || ! $task->next_run_at->equalTo($nextRunAt)) {
                $task->update(['next_run_at' => $nextRunAt]);
            }
        });
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
