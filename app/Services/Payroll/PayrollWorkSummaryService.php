<?php

namespace App\Services\Payroll;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Services\AppSettings\AppSettingService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class PayrollWorkSummaryService
{
    public function __construct(private readonly AppSettingService $settings)
    {
    }

    public function summarize(Employee $employee, string|Carbon $from, string|Carbon $to): array
    {
        $timezone = (string) $this->settings->get('organization.timezone', config('app.timezone'));
        $start = Carbon::parse($from, $timezone)->startOfDay();
        $end = Carbon::parse($to, $timezone)->endOfDay();
        $workWeekdays = array_map('intval', (array) $this->settings->get('payroll.work_weekdays', [1, 2, 3, 4, 5]));
        $scheduledStart = (string) $this->settings->get('payroll.scheduled_start_time', '09:00');
        $scheduledEnd = (string) $this->settings->get('payroll.scheduled_end_time', '18:00');
        $graceMinutes = (int) $this->settings->get('payroll.grace_minutes', 0);

        $attendances = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('created_at')
            ->get()
            ->groupBy(fn (Attendance $attendance) => $attendance->date->toDateString());

        $leaveRequests = LeaveRequest::query()
            ->with('leaveType')
            ->where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())
            ->get();

        $summary = [
            'scheduled_days' => 0.0,
            'days_worked' => 0.0,
            'paid_leave_days' => 0.0,
            'unpaid_leave_days' => 0.0,
            'absent_days' => 0.0,
            'late_minutes' => 0,
            'undertime_minutes' => 0,
            'exceptions' => [],
            'attendance_records' => $attendances->flatten()->count(),
            'approved_leave_requests' => $leaveRequests->count(),
        ];

        foreach (CarbonPeriod::create($start->copy()->startOfDay(), $end->copy()->startOfDay()) as $date) {
            if (! in_array($date->dayOfWeekIso, $workWeekdays, true)) {
                continue;
            }
            if ($employee->hire_date && $date->lt($employee->hire_date->copy()->startOfDay())) {
                continue;
            }

            $summary['scheduled_days']++;
            $dateKey = $date->toDateString();
            $dailyAttendance = $attendances->get($dateKey, collect());
            $attendance = $dailyAttendance->first();
            $leave = $this->leaveFractionsForDate($leaveRequests, $date, $timezone);
            $paidLeave = min(1, $leave['paid']);
            $unpaidLeave = min(1 - $paidLeave, $leave['unpaid']);
            $summary['paid_leave_days'] += $paidLeave;
            $summary['unpaid_leave_days'] += $unpaidLeave;

            if ($dailyAttendance->count() > 1) {
                $summary['exceptions'][] = [
                    'code' => 'duplicate_attendance',
                    'date' => $dateKey,
                    'message' => 'Multiple attendance records require review.',
                ];
            }

            if (! $attendance) {
                $remaining = max(0, 1 - $paidLeave - $unpaidLeave);
                $summary['absent_days'] += $remaining;
                if ($remaining > 0) {
                    $summary['exceptions'][] = [
                        'code' => 'missing_attendance',
                        'date' => $dateKey,
                        'message' => 'No attendance or approved leave covers the full workday.',
                    ];
                }

                continue;
            }

            $summary['days_worked'] += max(0, 1 - $paidLeave - $unpaidLeave);
            if (! $attendance->time_in) {
                $summary['exceptions'][] = [
                    'code' => 'missing_time_in',
                    'date' => $dateKey,
                    'message' => 'Attendance record has no time in.',
                ];
            } else {
                $timeIn = $attendance->time_in->copy()->setTimezone($timezone);
                $expectedIn = Carbon::parse("{$dateKey} {$scheduledStart}", $timezone)->addMinutes($graceMinutes);
                if ($timeIn->gt($expectedIn)) {
                    $summary['late_minutes'] += $expectedIn->diffInMinutes($timeIn);
                }
            }

            if (! $attendance->time_out) {
                $summary['exceptions'][] = [
                    'code' => 'missing_time_out',
                    'date' => $dateKey,
                    'message' => 'Attendance record has no time out; undertime was not deducted.',
                ];
            } else {
                $timeOut = $attendance->time_out->copy()->setTimezone($timezone);
                $expectedOut = Carbon::parse("{$dateKey} {$scheduledEnd}", $timezone);
                if ($timeOut->lt($expectedOut)) {
                    $summary['undertime_minutes'] += $timeOut->diffInMinutes($expectedOut);
                }
            }
        }

        foreach (['scheduled_days', 'days_worked', 'paid_leave_days', 'unpaid_leave_days', 'absent_days'] as $key) {
            $summary[$key] = round($summary[$key], 2);
        }

        return $summary;
    }

    private function leaveFractionsForDate($leaveRequests, Carbon $date, string $timezone): array
    {
        $fractions = ['paid' => 0.0, 'unpaid' => 0.0];
        $hoursPerDay = max(1, (float) $this->settings->get('payroll.hours_per_day', 8));

        foreach ($leaveRequests as $leave) {
            $startDate = Carbon::parse($leave->start_date->toDateString(), $timezone)->startOfDay();
            $endDate = Carbon::parse($leave->end_date->toDateString(), $timezone)->startOfDay();
            if ($date->lt($startDate) || $date->gt($endDate)) {
                continue;
            }

            $fraction = 1.0;
            if ($startDate->isSameDay($endDate) && $leave->start_time && $leave->end_time) {
                $leaveStart = Carbon::parse($date->toDateString().' '.$leave->start_time, $timezone);
                $leaveEnd = Carbon::parse($date->toDateString().' '.$leave->end_time, $timezone);
                $fraction = min(1, max(0, $leaveStart->diffInMinutes($leaveEnd) / ($hoursPerDay * 60)));
            }

            $key = $leave->leaveType?->is_paid ? 'paid' : 'unpaid';
            $fractions[$key] = min(1, $fractions[$key] + $fraction);
        }

        return $fractions;
    }
}
