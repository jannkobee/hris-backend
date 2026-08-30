<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\LeaveCredit;
use App\Models\LeaveCreditLog;
use App\Models\LeaveCreditSetting;
use App\Models\LeaveType;
use App\Services\LeaveAccrual\LeaveCreditAccrualService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveCreditAccrualTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_employee_receives_an_enabled_grant_on_hire_rule_once(): void
    {
        $leaveType = LeaveType::create([
            'name' => 'New Hire Vacation',
            'default_days' => 5,
            'is_paid' => true,
        ]);
        $setting = LeaveCreditSetting::create([
            'leave_type_id' => $leaveType->id,
            'name' => 'New hire vacation credit',
            'credit_amount' => 5,
            'frequency' => 'annually',
            'run_months' => [1],
            'minimum_service_months' => 0,
            'grant_on_hire' => true,
            'is_active' => true,
        ]);
        $employee = Employee::create([
            'employee_no' => 'NEW-HIRE-001',
            'hire_date' => now()->toDateString(),
        ]);

        $service = app(LeaveCreditAccrualService::class);

        $this->assertSame(1, $service->accrueEmployee($employee)['credited']);
        $this->assertSame(1, $service->accrueEmployee($employee)['skipped']);
        $this->assertSame('5.00', LeaveCredit::query()->firstOrFail()->total_earned);
        $this->assertSame($setting->id, LeaveCreditLog::query()->first()?->leave_credit_setting_id);
    }

    public function test_leave_credit_cron_credits_due_employees_and_is_safe_to_run_again(): void
    {
        $leaveType = LeaveType::create([
            'name' => 'Monthly Sick Leave',
            'default_days' => 1,
            'is_paid' => true,
        ]);
        LeaveCreditSetting::create([
            'leave_type_id' => $leaveType->id,
            'name' => 'Monthly sick leave',
            'credit_amount' => 1.25,
            'frequency' => 'monthly',
            'run_months' => [now()->month],
            'minimum_service_months' => 0,
            'grant_on_hire' => false,
            'is_active' => true,
        ]);
        Employee::create([
            'employee_no' => 'CRON-001',
            'hire_date' => now()->subMonth()->toDateString(),
        ]);

        $this->artisan('leave-credits:accrue')
            ->expectsOutputToContain('Credited: 1')
            ->assertSuccessful();
        $this->artisan('leave-credits:accrue')
            ->expectsOutputToContain('Already credited: 1')
            ->assertSuccessful();

        $this->assertSame('1.25', LeaveCredit::query()->firstOrFail()->total_earned);
    }
}
