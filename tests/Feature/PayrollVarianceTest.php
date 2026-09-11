<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\Role;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollVarianceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_retrieve_payroll_variance_analysis(): void
    {
        $organization = app(TenantContext::class)->organization();
        $organization->update([
            'country_code' => 'PH',
            'plan_code' => Organization::PLAN_ENTERPRISE,
        ]);

        $role = Role::firstOrCreate(['name' => 'Admin']);
        $admin = User::factory()->create(['role_id' => $role->id]);

        $employeeUser = User::factory()->create(['role_id' => $role->id, 'first_name' => 'Maria', 'last_name' => 'Clara']);
        $employee = Employee::create([
            'user_id' => $employeeUser->id,
            'employee_no' => 'EMP-VAR-001',
            'basic_monthly_salary' => 40000,
            'pay_schedule' => 'semi_monthly',
            'hire_date' => '2024-01-01',
        ]);

        // Period 1 (Previous)
        $period1 = PayrollPeriod::create([
            'name' => 'Period 1 - 2026-08-01 to 2026-08-15',
            'date_from' => '2026-08-01',
            'date_to' => '2026-08-15',
            'payout_date' => '2026-08-15',
            'frequency' => 'semi_monthly',
            'status' => 'paid',
            'total_gross' => 20000,
            'total_net' => 17500,
            'created_by' => $admin->id,
        ]);

        PayrollItem::create([
            'payroll_period_id' => $period1->id,
            'employee_id' => $employee->id,
            'gross_pay' => 20000,
            'net_pay' => 17500,
            'overtime_pay' => 0,
            'total_deductions' => 2500,
            'calculation_snapshot' => [],
        ]);

        // Period 2 (Current with 25% gross increase due to overtime)
        $period2 = PayrollPeriod::create([
            'name' => 'Period 2 - 2026-08-16 to 2026-08-31',
            'date_from' => '2026-08-16',
            'date_to' => '2026-08-31',
            'payout_date' => '2026-08-31',
            'frequency' => 'semi_monthly',
            'status' => 'processed',
            'total_gross' => 25000,
            'total_net' => 21500,
            'created_by' => $admin->id,
        ]);

        PayrollItem::create([
            'payroll_period_id' => $period2->id,
            'employee_id' => $employee->id,
            'gross_pay' => 25000,
            'net_pay' => 21500,
            'overtime_pay' => 5000,
            'total_deductions' => 3500,
            'calculation_snapshot' => [],
        ]);

        $this->actingAs($admin, 'sanctum');

        $response = $this->getJson("/backend/api/v1/payroll-periods/{$period2->id}/variance");

        $response->assertOk();
        $response->assertJsonStructure([
            'message',
            'data' => [
                'current_period' => ['id', 'name', 'total_gross', 'total_net'],
                'previous_period' => ['id', 'name'],
                'threshold_percent',
                'total_items',
                'significant_variances_count',
                'items' => [
                    '*' => [
                        'employee_id',
                        'employee_no',
                        'employee_name',
                        'current' => ['gross_pay', 'net_pay', 'overtime_pay', 'total_deductions'],
                        'previous' => ['gross_pay', 'net_pay'],
                        'deltas' => ['gross_pay', 'gross_pay_percentage', 'net_pay', 'net_pay_percentage'],
                        'is_significant_variance',
                    ],
                ],
            ],
        ]);

        $data = $response->json('data');
        $this->assertSame(1, $data['total_items']);
        $this->assertSame(1, $data['significant_variances_count']);
        $this->assertTrue($data['items'][0]['is_significant_variance']);
        $this->assertSame(5000.0, (float) $data['items'][0]['deltas']['gross_pay']);
        $this->assertSame(25.0, (float) $data['items'][0]['deltas']['gross_pay_percentage']);
    }

    public function test_variance_without_previous_period_returns_null_previous(): void
    {
        $organization = app(TenantContext::class)->organization();
        $organization->update([
            'country_code' => 'PH',
            'plan_code' => Organization::PLAN_ENTERPRISE,
        ]);

        $role = Role::firstOrCreate(['name' => 'Admin']);
        $admin = User::factory()->create(['role_id' => $role->id]);

        $period = PayrollPeriod::create([
            'name' => 'First Period - 2026-08-01 to 2026-08-15',
            'date_from' => '2026-08-01',
            'date_to' => '2026-08-15',
            'payout_date' => '2026-08-15',
            'frequency' => 'semi_monthly',
            'status' => 'draft',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin, 'sanctum');

        $response = $this->getJson("/backend/api/v1/payroll-periods/{$period->id}/variance");

        $response->assertOk();
        $this->assertNull($response->json('data.previous_period'));
        $this->assertSame(0, $response->json('data.total_items'));
    }
}

