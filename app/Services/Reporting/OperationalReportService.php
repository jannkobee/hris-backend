<?php

namespace App\Services\Reporting;

use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\Overtime;
use App\Models\PayrollPeriod;

class OperationalReportService
{
    public function run(string $type, string $from, string $to): array
    {
        return match ($type) {
            'attendance_summary' => $this->attendance($from, $to),
            'leave_summary' => $this->leave($from, $to),
            'overtime_summary' => $this->overtime($from, $to),
            'payroll_register' => $this->payroll($from, $to),
            'workforce_cost_summary' => $this->workforceCost($from, $to),
        };
    }

    private function attendance(string $from, string $to): array
    {
        $rows = Attendance::query()->with('employee.user')
            ->whereBetween('date', [$from, $to])->orderBy('date')->get()
            ->map(fn (Attendance $attendance) => [
                'date' => $attendance->date?->toDateString(),
                'employee_no' => $attendance->employee?->employee_no,
                'employee' => $attendance->employee?->user?->full_name,
                'time_in' => $attendance->time_in?->format('H:i'),
                'time_out' => $attendance->time_out?->format('H:i'),
                'late_minutes' => $attendance->late_minutes,
                'undertime_minutes' => $attendance->undertime_minutes,
                'exceptions' => implode(', ', $attendance->exception_codes ?? []),
            ])->all();

        return ['title' => 'Attendance summary', 'columns' => array_keys($rows[0] ?? $this->attendanceColumns()), 'rows' => $rows];
    }

    private function leave(string $from, string $to): array
    {
        $rows = LeaveRequest::query()->with('employee.user', 'leaveType')
            ->whereDate('start_date', '<=', $to)->whereDate('end_date', '>=', $from)->orderBy('start_date')->get()
            ->map(fn (LeaveRequest $leave) => [
                'employee_no' => $leave->employee?->employee_no,
                'employee' => $leave->employee?->user?->full_name,
                'leave_type' => $leave->leaveType?->name,
                'from' => $leave->start_date?->toDateString(),
                'to' => $leave->end_date?->toDateString(),
                'days' => $leave->start_date?->diffInDays($leave->end_date) + 1,
                'status' => $leave->status,
            ])->all();

        return ['title' => 'Leave summary', 'columns' => array_keys($rows[0] ?? ['employee_no' => null, 'employee' => null, 'leave_type' => null, 'from' => null, 'to' => null, 'days' => null, 'status' => null]), 'rows' => $rows];
    }

    private function overtime(string $from, string $to): array
    {
        $rows = Overtime::query()->with('employee.user')->whereBetween('date', [$from, $to])->orderBy('date')->get()
            ->map(fn (Overtime $overtime) => [
                'date' => $overtime->date?->toDateString(),
                'employee_no' => $overtime->employee?->employee_no,
                'employee' => $overtime->employee?->user?->full_name,
                'hours' => $overtime->hours,
                'premium_hours' => $overtime->premium_hours,
                'day_type' => $overtime->day_type,
                'status' => $overtime->status,
            ])->all();

        return ['title' => 'Overtime summary', 'columns' => array_keys($rows[0] ?? ['date' => null, 'employee_no' => null, 'employee' => null, 'hours' => null, 'premium_hours' => null, 'day_type' => null, 'status' => null]), 'rows' => $rows];
    }

    private function payroll(string $from, string $to): array
    {
        $rows = PayrollPeriod::query()->with('items.employee.user')->whereBetween('date_to', [$from, $to])
            ->whereIn('status', ['approved', 'paid'])->orderBy('date_to')->get()->flatMap(
                fn (PayrollPeriod $period) => $period->items->map(fn ($item) => [
                    'period' => $period->name,
                    'pay_date' => $period->payout_date?->toDateString(),
                    'employee_no' => $item->employee?->employee_no,
                    'employee' => $item->employee?->user?->full_name,
                    'gross_pay' => $item->gross_pay,
                    'deductions' => $item->total_deductions,
                    'net_pay' => $item->net_pay,
                    'status' => $period->status,
                ])
            )->values()->all();

        return ['title' => 'Payroll register', 'columns' => array_keys($rows[0] ?? ['period' => null, 'pay_date' => null, 'employee_no' => null, 'employee' => null, 'gross_pay' => null, 'deductions' => null, 'net_pay' => null, 'status' => null]), 'rows' => $rows];
    }

    private function workforceCost(string $from, string $to): array
    {
        $items = PayrollPeriod::query()
            ->with('items.employee.department')
            ->whereIn('status', ['approved', 'paid'])
            ->whereBetween('date_to', [$from, $to])
            ->get()
            ->flatMap(fn (PayrollPeriod $period) => $period->items);

        $rows = $items->groupBy(fn ($item) => $item->employee?->department?->name ?? 'Unassigned')
            ->map(function ($items, string $department): array {
                $grossPay = (float) $items->sum('gross_pay');
                $employerContributions = (float) $items->sum(fn ($item) => (float) $item->sss_employer + (float) $item->sss_ec_employer
                    + (float) $item->philhealth_employer + (float) $item->pagibig_employer
                );

                return [
                    'department' => $department,
                    'employees' => $items->pluck('employee_id')->unique()->count(),
                    'gross_pay' => round($grossPay, 2),
                    'overtime_pay' => round((float) $items->sum('overtime_pay'), 2),
                    'employer_contributions' => round($employerContributions, 2),
                    'total_employer_cost' => round($grossPay + $employerContributions, 2),
                    'net_pay' => round((float) $items->sum('net_pay'), 2),
                ];
            })->sortByDesc('total_employer_cost')->values()->all();

        return [
            'title' => 'Workforce cost summary',
            'columns' => array_keys($rows[0] ?? [
                'department' => null, 'employees' => null, 'gross_pay' => null,
                'overtime_pay' => null, 'employer_contributions' => null,
                'total_employer_cost' => null, 'net_pay' => null,
            ]),
            'rows' => $rows,
        ];
    }

    private function attendanceColumns(): array
    {
        return ['date' => null, 'employee_no' => null, 'employee' => null, 'time_in' => null, 'time_out' => null, 'late_minutes' => null, 'undertime_minutes' => null, 'exceptions' => null];
    }
}
