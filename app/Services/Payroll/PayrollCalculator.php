<?php

namespace App\Services\Payroll;

use App\Models\Employee;
use App\Services\AppSettings\AppSettingService;

class PayrollCalculator
{
    public function __construct(private readonly AppSettingService $settings)
    {
    }

    public function calculate(
        Employee $employee,
        string $frequency,
        float $overtimePay = 0,
        array $adjustments = [],
        array $workSummary = []
    ): array {
        $monthlySalary = (float) $employee->basic_monthly_salary;
        $divisor = $frequency === 'semi_monthly' ? 2 : 1;
        $basicPay = $monthlySalary / $divisor;
        $allowances = (float) ($adjustments['allowances'] ?? 0);
        $otherEarnings = (float) ($adjustments['other_earnings'] ?? 0);
        $otherDeductions = (float) ($adjustments['other_deductions'] ?? 0);
        $grossPay = $basicPay + $overtimePay + $allowances + $otherEarnings;
        $dailyRate = $monthlySalary / max(1, (float) $this->settings->get('payroll.work_days_per_month', 22));
        $minuteRate = $dailyRate / max(1, (float) $this->settings->get('payroll.hours_per_day', 8) * 60);
        $attendanceEnabled = (bool) $this->settings->get('payroll.attendance_calculation_enabled', true);
        $absenceDeduction = $attendanceEnabled && $this->settings->get('payroll.deduct_absences', true)
            ? (float) ($workSummary['absent_days'] ?? 0) * $dailyRate
            : 0;
        $lateUndertimeDeduction = $attendanceEnabled && $this->settings->get('payroll.deduct_late_undertime', true)
            ? ((float) ($workSummary['late_minutes'] ?? 0) + (float) ($workSummary['undertime_minutes'] ?? 0)) * $minuteRate
            : 0;
        $unpaidLeaveDeduction = $attendanceEnabled && $this->settings->get('payroll.deduct_unpaid_leave', true)
            ? (float) ($workSummary['unpaid_leave_days'] ?? 0) * $dailyRate
            : 0;

        $sssMsc = $this->sssMonthlySalaryCredit($monthlySalary);
        $sssEmployee = $sssMsc * (float) $this->settings->get('payroll.sss_employee_rate', 0.05) / $divisor;
        $sssEmployer = $sssMsc * (float) $this->settings->get('payroll.sss_employer_rate', 0.10) / $divisor;
        $sssEc = ($sssMsc <= (float) $this->settings->get('payroll.sss_ec_threshold', 14500)
            ? (float) $this->settings->get('payroll.sss_ec_low', 10)
            : (float) $this->settings->get('payroll.sss_ec_high', 30)) / $divisor;

        $philhealthBasis = min(
            max($monthlySalary, (float) $this->settings->get('payroll.philhealth_salary_floor', 10000)),
            (float) $this->settings->get('payroll.philhealth_salary_ceiling', 100000)
        );
        $philhealthMonthly = $philhealthBasis * (float) $this->settings->get('payroll.philhealth_rate', 0.05);
        $philhealthEmployee = $philhealthMonthly / 2 / $divisor;
        $philhealthEmployer = $philhealthMonthly / 2 / $divisor;

        $pagibigBasis = min($monthlySalary, (float) $this->settings->get('payroll.pagibig_max_salary', 10000));
        $pagibigEmployeeRate = $monthlySalary <= (float) $this->settings->get('payroll.pagibig_rate_threshold', 1500)
            ? (float) $this->settings->get('payroll.pagibig_employee_rate_low', 0.01)
            : (float) $this->settings->get('payroll.pagibig_employee_rate', 0.02);
        $pagibigEmployee = $pagibigBasis * $pagibigEmployeeRate / $divisor;
        $pagibigEmployer = $pagibigBasis * (float) $this->settings->get('payroll.pagibig_employer_rate', 0.02) / $divisor;

        $workDeductions = $absenceDeduction + $lateUndertimeDeduction + $unpaidLeaveDeduction;
        $taxablePay = max(0, $grossPay - $workDeductions - $sssEmployee - $philhealthEmployee - $pagibigEmployee);
        $withholdingTax = $this->withholdingTax($taxablePay, $frequency);
        $totalDeductions = $workDeductions + $sssEmployee + $philhealthEmployee + $pagibigEmployee + $withholdingTax + $otherDeductions;

        $snapshotKeys = [
            'payroll.currency',
            'payroll.work_days_per_month',
            'payroll.hours_per_day',
            'payroll.overtime_multiplier',
            'payroll.attendance_calculation_enabled',
            'payroll.work_weekdays',
            'payroll.scheduled_start_time',
            'payroll.scheduled_end_time',
            'payroll.grace_minutes',
            'payroll.deduct_absences',
            'payroll.deduct_late_undertime',
            'payroll.deduct_unpaid_leave',
            'payroll.sss_employee_rate',
            'payroll.sss_employer_rate',
            'payroll.sss_min_msc',
            'payroll.sss_max_msc',
            'payroll.sss_ec_low',
            'payroll.sss_ec_high',
            'payroll.sss_ec_threshold',
            'payroll.philhealth_rate',
            'payroll.philhealth_salary_floor',
            'payroll.philhealth_salary_ceiling',
            'payroll.pagibig_employee_rate_low',
            'payroll.pagibig_employee_rate',
            'payroll.pagibig_employer_rate',
            'payroll.pagibig_rate_threshold',
            'payroll.pagibig_max_salary',
        ];

        return [
            'scheduled_days' => $workSummary['scheduled_days'] ?? 0,
            'days_worked' => $workSummary['days_worked'] ?? 0,
            'paid_leave_days' => $workSummary['paid_leave_days'] ?? 0,
            'unpaid_leave_days' => $workSummary['unpaid_leave_days'] ?? 0,
            'absent_days' => $workSummary['absent_days'] ?? 0,
            'late_minutes' => $workSummary['late_minutes'] ?? 0,
            'undertime_minutes' => $workSummary['undertime_minutes'] ?? 0,
            'basic_pay' => $this->money($basicPay),
            'overtime_pay' => $this->money($overtimePay),
            'allowances' => $this->money($allowances),
            'other_earnings' => $this->money($otherEarnings),
            'gross_pay' => $this->money($grossPay),
            'absence_deduction' => $this->money($absenceDeduction),
            'late_undertime_deduction' => $this->money($lateUndertimeDeduction),
            'unpaid_leave_deduction' => $this->money($unpaidLeaveDeduction),
            'sss_employee' => $this->money($sssEmployee),
            'sss_employer' => $this->money($sssEmployer),
            'sss_ec_employer' => $this->money($sssEc),
            'philhealth_employee' => $this->money($philhealthEmployee),
            'philhealth_employer' => $this->money($philhealthEmployer),
            'pagibig_employee' => $this->money($pagibigEmployee),
            'pagibig_employer' => $this->money($pagibigEmployer),
            'withholding_tax' => $this->money($withholdingTax),
            'other_deductions' => $this->money($otherDeductions),
            'total_deductions' => $this->money($totalDeductions),
            'net_pay' => $this->money(max(0, $grossPay - $totalDeductions)),
            'notes' => $adjustments['notes'] ?? null,
            'exceptions' => $workSummary['exceptions'] ?? [],
            'calculation_snapshot' => [
                'calculated_at' => now()->toIso8601String(),
                'frequency' => $frequency,
                'monthly_salary' => $this->money($monthlySalary),
                'sss_msc' => $this->money($sssMsc),
                'philhealth_basis' => $this->money($philhealthBasis),
                'pagibig_basis' => $this->money($pagibigBasis),
                'taxable_pay' => $this->money($taxablePay),
                'daily_rate' => $this->money($dailyRate),
                'minute_rate' => round($minuteRate, 4),
                'attendance_records' => $workSummary['attendance_records'] ?? 0,
                'approved_leave_requests' => $workSummary['approved_leave_requests'] ?? 0,
                'tax_table' => 'BIR revised withholding tax table effective 2023-01-01',
                'settings' => collect($snapshotKeys)->mapWithKeys(
                    fn (string $key) => [$key => $this->settings->get($key)]
                )->all(),
            ],
        ];
    }

    public function overtimePay(Employee $employee, float $hours): float
    {
        $days = max(1, (float) $this->settings->get('payroll.work_days_per_month', 22));
        $hoursPerDay = max(1, (float) $this->settings->get('payroll.hours_per_day', 8));
        $multiplier = (float) $this->settings->get('payroll.overtime_multiplier', 1.25);

        return $this->money(((float) $employee->basic_monthly_salary / $days / $hoursPerDay) * $hours * $multiplier);
    }

    private function sssMonthlySalaryCredit(float $monthlySalary): float
    {
        $minimum = (float) $this->settings->get('payroll.sss_min_msc', 5000);
        $maximum = (float) $this->settings->get('payroll.sss_max_msc', 35000);
        $bounded = min(max($monthlySalary, $minimum), $maximum);

        return min($maximum, max($minimum, floor(($bounded + 250) / 500) * 500));
    }

    private function withholdingTax(float $taxablePay, string $frequency): float
    {
        $brackets = $frequency === 'semi_monthly'
            ? [
                [10417, 0, 0, 0],
                [16667, 10417, 0, .15],
                [33333, 16667, 937.50, .20],
                [83333, 33333, 4270.70, .25],
                [333333, 83333, 16770.70, .30],
                [INF, 333333, 91770.70, .35],
            ]
            : [
                [20833, 0, 0, 0],
                [33333, 20833, 0, .15],
                [66667, 33333, 1875, .20],
                [166667, 66667, 8541.80, .25],
                [666667, 166667, 33541.80, .30],
                [INF, 666667, 183541.80, .35],
            ];

        foreach ($brackets as [$ceiling, $base, $fixed, $rate]) {
            if ($taxablePay < $ceiling) {
                return $this->money($fixed + max(0, $taxablePay - $base) * $rate);
            }
        }

        return 0;
    }

    private function money(float $amount): float
    {
        return round($amount, 2);
    }
}
