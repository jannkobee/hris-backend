<?php

namespace Tests\Unit;

use App\Models\Employee;
use App\Services\AppSettings\AppSettingService;
use App\Services\Payroll\PayrollCalculator;
use Tests\TestCase;

class PayrollCalculatorTest extends TestCase
{
    public function test_it_uses_current_statutory_defaults_for_a_semi_monthly_payslip(): void
    {
        $calculator = new PayrollCalculator(app(AppSettingService::class));
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
        $calculator = new PayrollCalculator(app(AppSettingService::class));
        $employee = new Employee(['basic_monthly_salary' => 22000]);

        $this->assertSame(625.0, $calculator->overtimePay($employee, 4));
    }
}
