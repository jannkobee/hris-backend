<?php

namespace Tests\Unit;

use App\Models\ScheduledTask;
use App\Services\Scheduling\ScheduledTaskScheduleService;
use Tests\TestCase;

class ScheduledTaskScheduleServiceTest extends TestCase
{
    public function test_it_builds_a_cron_expression_for_each_supported_frequency(): void
    {
        $service = app(ScheduledTaskScheduleService::class);

        $this->assertSame('30 9 * * *', $service->cronExpression(new ScheduledTask([
            'frequency' => 'daily', 'run_time' => '09:30',
        ])));
        $this->assertSame('0 8 * * 1,5', $service->cronExpression(new ScheduledTask([
            'frequency' => 'weekly', 'run_time' => '08:00', 'run_days' => [1, 5],
        ])));
        $this->assertSame('15 17 20 1,7 *', $service->cronExpression(new ScheduledTask([
            'frequency' => 'yearly', 'run_time' => '17:15', 'run_day_of_month' => 20, 'run_months' => [1, 7],
        ])));
    }
}
