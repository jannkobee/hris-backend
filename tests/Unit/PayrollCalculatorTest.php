<?php

namespace Tests\Unit;

use App\Models\BenefitEnrollment;
use App\Models\BenefitPlan;
use App\Models\Employee;
use App\Services\AppSettings\AppSettingService;
use App\Services\Payroll\PayrollCalculator;
use App\Services\Payroll\StatutoryRuleResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_uses_current_statutory_defaults_for_a_semi_monthly_payslip(): void
    {
        $calculator = new PayrollCalculator(app(AppSettingService::class), app(StatutoryRuleResolver::class));
        $employee = new Employee([
            'basic_monthly_salary' => 30000,
            'pay_schedule' => 'semi_monthly',
        ]);

        $result = $calculator->calculate($employee, 'semi_monthly');

        $this->assertSame(15000.0, $result['basic_pay']);
        $this->assertSame(750.0, $result['sss_employee']);
        $this->assertSame(1500.0, $result['sss_employer']);
        $this->assertSame(15.0, $result['sss_ec_employer']);
        $this->assertSame(375.0, $result['philhealth_employee']);
        $this->assertSame(375.0, $result['philhealth_employer']);
        $this->assertSame(100.0, $result['pagibig_employee']);
        $this->assertSame(100.0, $result['pagibig_employer']);
        $this->assertSame(503.7, $result['withholding_tax']);
        $this->assertSame(13271.3, $result['net_pay']);
        $this->assertSame('BIR revised withholding tax table effective 2023-01-01', $result['calculation_snapshot']['tax_table']);
    }

    public function test_overtime_uses_the_configured_hourly_divisor_and_multiplier(): void
    {
        $calculator = new PayrollCalculator(app(AppSettingService::class), app(StatutoryRuleResolver::class));
        $employee = new Employee(['basic_monthly_salary' => 22000]);

        $this->assertSame(500.0, $calculator->overtimePay($employee, 4));
    }

    public function test_it_deducts_active_benefit_contributions_each_pay_period(): void
    {
        $employee = Employee::create(['employee_no' => 'BEN-001', 'basic_monthly_salary' => 30000]);
        $plan = BenefitPlan::create(['name' => 'Medical', 'employee_contribution' => 1000, 'employer_contribution' => 0, 'is_active' => true]);
        BenefitEnrollment::create(['benefit_plan_id' => $plan->id, 'employee_id' => $employee->id, 'effective_from' => now()->startOfMonth(), 'status' => 'active']);

        $result = app(PayrollCalculator::class)->calculate($employee, 'semi_monthly', effectiveDate: now()->toDateString());

        $this->assertSame(500.0, $result['benefit_deductions']);
        $this->assertSame(12771.3, $result['net_pay']);
    }
}
