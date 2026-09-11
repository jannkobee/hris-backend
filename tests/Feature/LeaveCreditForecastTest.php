<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\LeaveCredit;
use App\Models\LeaveCreditSetting;
use App\Models\LeaveType;
use App\Models\Role;
use App\Models\User;
use App\Tenancy\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveCreditForecastTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_leave_credit_forecast(): void
    {
        $response = $this->getJson('/backend/api/v1/leave-credits/forecast');

        $response->assertUnauthorized();
    }

    public function test_user_without_permission_cannot_access_leave_credit_forecast(): void
    {
        $role = Role::firstOrCreate(['name' => 'Restricted User']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/backend/api/v1/leave-credits/forecast');

        $response->assertForbidden();
    }

    public function test_authenticated_user_with_permission_can_forecast_leave_credit_accruals(): void
    {
        $role = Role::firstOrCreate(['name' => 'Admin']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $leaveType = LeaveType::create([
            'name' => 'Vacation Leave',
            'code' => 'VL',
            'default_days' => 15,
            'is_paid' => true,
        ]);

        $setting = LeaveCreditSetting::create([
            'leave_type_id' => $leaveType->id,
            'name' => 'Monthly Vacation Accrual',
            'credit_amount' => 1.25,
            'frequency' => 'monthly',
            'run_months' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
            'minimum_service_months' => 0,
            'grant_on_hire' => false,
            'initial_credit_amount' => 0,
            'is_active' => true,
        ]);

        $employee = Employee::create([
            'user_id' => $user->id,
            'employee_no' => 'EMP-FORECAST-001',
            'hire_date' => now()->subMonths(6)->toDateString(),
        ]);

        // Existing credit ledger: 5 earned, 2 used -> 3 remaining
        LeaveCredit::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'year' => now()->year,
            'total_earned' => 5.0,
            'used' => 2.0,
        ]);

        $targetDate = now()->addMonths(4)->endOfMonth();

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson(sprintf(
            '/backend/api/v1/leave-credits/forecast?employee_id=%s&target_date=%s',
            $employee->id,
            $targetDate->toDateString()
        ));

        $response->assertOk();
        $response->assertJsonStructure([
            'message',
            'data' => [
                'employee' => ['id', 'employee_no', 'name'],
                'as_of_date',
                'target_date',
                'forecasts' => [
                    '*' => [
                        'leave_type_id',
                        'leave_type_name',
                        'current_balance',
                        'projected_accruals',
                        'projected_balance',
                        'schedule',
                    ],
                ],
            ],
        ]);

        $data = $response->json('data');
        $this->assertSame($employee->id, $data['employee']['id']);
        $this->assertNotEmpty($data['forecasts']);

        $vlForecast = collect($data['forecasts'])->firstWhere('leave_type_id', $leaveType->id);
        $this->assertNotNull($vlForecast);
        $this->assertEquals(3.0, $vlForecast['current_balance']);
        // 4 months of 1.25 accruals = 5.00
        $this->assertEquals(5.0, $vlForecast['projected_accruals']);
        $this->assertEquals(8.0, $vlForecast['projected_balance']);
        $this->assertCount(4, $vlForecast['schedule']);
    }

    public function test_ineligible_employee_does_not_accrue_credits_if_tenure_not_met(): void
    {
        $role = Role::firstOrCreate(['name' => 'Admin']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $leaveType = LeaveType::create([
            'name' => 'Tenure Sick Leave',
            'code' => 'SL-TENURE',
            'default_days' => 10,
            'is_paid' => true,
        ]);

        // Requires 12 months minimum tenure
        LeaveCreditSetting::create([
            'leave_type_id' => $leaveType->id,
            'name' => 'Senior Sick Accrual',
            'credit_amount' => 1.0,
            'frequency' => 'monthly',
            'run_months' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
            'minimum_service_months' => 12,
            'is_active' => true,
        ]);

        // Employee hired only 1 month ago
        $employee = Employee::create([
            'user_id' => $user->id,
            'employee_no' => 'EMP-TENURE-002',
            'hire_date' => now()->subMonth()->toDateString(),
        ]);

        $targetDate = now()->addMonths(3)->endOfMonth();

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson(sprintf(
            '/backend/api/v1/leave-credits/forecast?employee_id=%s&target_date=%s',
            $employee->id,
            $targetDate->toDateString()
        ));

        $response->assertOk();
        $vlForecast = collect($response->json('data.forecasts'))->firstWhere('leave_type_id', $leaveType->id);
        $this->assertNotNull($vlForecast);
        $this->assertEquals(0.0, $vlForecast['projected_accruals']);
        $this->assertEmpty($vlForecast['schedule']);
    }
}

