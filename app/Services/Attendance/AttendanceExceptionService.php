<?php

namespace App\Services\Attendance;

use App\Models\Attendance;
use App\Models\ShiftAssignment;
use App\Services\AppSettings\AppSettingService;
use Carbon\Carbon;

class AttendanceExceptionService
{
    public function __construct(private readonly AppSettingService $settings)
    {
    }

    public function apply(Attendance $attendance): void
    {
        $shift = ShiftAssignment::query()->where('employee_id', $attendance->employee_id)->whereDate('work_date', $attendance->date)->first();
        $codes = [];
        $late = 0;
        $undertime = 0;
        if (! $shift) {
            $codes[] = 'unscheduled_attendance';
        } else {
            $tz = $this->settings->get('organization.timezone', config('app.timezone'));
            $start = Carbon::parse($attendance->date->toDateString().' '.$shift->start_time, $tz);
            $end = Carbon::parse($attendance->date->toDateString().' '.$shift->end_time, $tz);
            if ($end->lte($start)) {
                $end->addDay();
            } if ($attendance->time_in) {
                $actual = $attendance->time_in->copy()->setTimezone($tz);
                $late = max(0, $start->copy()->addMinutes($shift->grace_minutes)->diffInMinutes($actual, false));
                if ($late) {
                    $codes[] = 'late';
                }
            } if (! $attendance->time_out) {
                $codes[] = 'missing_clock_out';
            } else {
                $actual = $attendance->time_out->copy()->setTimezone($tz);
                $undertime = max(0, $actual->diffInMinutes($end, false));
                if ($undertime) {
                    $codes[] = 'undertime';
                }
            }
        } $attendance->update(['shift_assignment_id' => $shift?->id, 'late_minutes' => $late, 'undertime_minutes' => $undertime, 'exception_codes' => $codes]);
    }
}
