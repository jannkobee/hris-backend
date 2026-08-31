<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\DashboardAnalyticsRequest;
use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\Overtime;
use App\Models\PayrollPeriod;
use App\Models\User;
use App\Models\WorkplaceMeeting;
use App\Services\AppSettings\AppSettingService;
use App\Services\AuditLog\AuditLogServiceInterface;
use App\Services\Dashboard\DailyInspirationService;
use App\Services\Plans\PlanEntitlementService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    private AppSettingService $settings;

    private DailyInspirationService $inspiration;

    private PlanEntitlementService $planEntitlements;

    private AuditLogServiceInterface $auditLogs;

    public function __construct(
        AppSettingService $settings,
        DailyInspirationService $inspiration,
        PlanEntitlementService $planEntitlements,
        AuditLogServiceInterface $auditLogs,
    ) {
        $this->settings = $settings;
        $this->inspiration = $inspiration;
        $this->planEntitlements = $planEntitlements;
        $this->auditLogs = $auditLogs;
    }

    public function overview(Request $request): JsonResponse
    {
        $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ]);
        $timezone = (string) $this->settings->get('organization.timezone', config('app.timezone'));
        $from = Carbon::parse($request->string('from'), $timezone)->startOfDay();
        $to = Carbon::parse($request->string('to'), $timezone)->endOfDay();
        if ($from->diffInDays($to) > 62) {
            throw ValidationException::withMessages(['to' => 'The dashboard calendar range cannot exceed 62 days.']);
        }

        /** @var User $user */
        $user = $request->user();
        $events = collect();

        if ($this->planEntitlements->allows($user->organization, 'workplace_hub')
            && $user->hasPermission('view-workplace-hub')) {
            $meetings = WorkplaceMeeting::query()
                ->with('room:id,name')
                ->where('status', '!=', 'cancelled')
                ->where('starts_at', '<=', $to->copy()->utc())
                ->where('ends_at', '>=', $from->copy()->utc())
                ->when(! $user->hasPermission('manage-company-meetings'), function (Builder $query) use ($user): void {
                    $query->where(fn (Builder $query) => $query
                        ->where('organizer_id', $user->id)
                        ->orWhereHas('attendees', fn (Builder $attendees) => $attendees->where('users.id', $user->id)));
                })
                ->get();
            foreach ($meetings as $meeting) {
                $events->push([
                    'id' => 'meeting-'.$meeting->id,
                    'record_id' => $meeting->id,
                    'type' => 'meeting',
                    'title' => $meeting->title,
                    'subtitle' => $meeting->room?->name ?? 'Online / no room',
                    'starts_at' => $meeting->starts_at->toIso8601String(),
                    'ends_at' => $meeting->ends_at->toIso8601String(),
                    'all_day' => false,
                    'color' => '#5b8def',
                ]);
            }
        }

        $leaveQuery = LeaveRequest::query()
            ->with('employee.user:id,first_name,middle_name,last_name', 'leaveType:id,name')
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $to->toDateString())
            ->whereDate('end_date', '>=', $from->toDateString());
        $canViewCompanyLeave = $user->hasAnyPermission(['manage-leave-requests', 'approve-leave-requests']);
        if (! $canViewCompanyLeave) {
            $employeeId = $user->employee?->id;
            $leaveQuery->when($employeeId, fn (Builder $query) => $query->where('employee_id', $employeeId))
                ->when(! $employeeId, fn (Builder $query) => $query->whereRaw('1 = 0'));
        }
        foreach ($leaveQuery->get() as $leave) {
            $leaveStart = Carbon::parse($leave->start_date->toDateString(), $timezone)->max($from);
            $leaveEnd = Carbon::parse($leave->end_date->toDateString(), $timezone)->min($to);
            foreach (CarbonPeriod::create($leaveStart, $leaveEnd) as $date) {
                $events->push([
                    'id' => 'leave-'.$leave->id.'-'.$date->toDateString(),
                    'record_id' => $leave->id,
                    'type' => 'leave',
                    'title' => $canViewCompanyLeave
                        ? ($leave->employee?->user?->full_name ?? 'Employee').' - '.($leave->leaveType?->name ?? 'Leave')
                        : $leave->leaveType?->name ?? 'Approved leave',
                    'subtitle' => 'Approved leave',
                    'starts_at' => $date->toDateString().'T00:00:00',
                    'ends_at' => $date->toDateString().'T23:59:59',
                    'all_day' => true,
                    'color' => '#8b6ccf',
                ]);
            }
        }

        $calendarAnnouncements = Announcement::query()
            ->where('is_active', true)
            ->whereBetween('published_at', [$from->toDateString(), $to->toDateString()])
            ->get();
        foreach ($calendarAnnouncements as $announcement) {
            $events->push([
                'id' => 'announcement-'.$announcement->id,
                'record_id' => $announcement->id,
                'type' => 'announcement',
                'title' => $announcement->title,
                'subtitle' => 'Announcement',
                'starts_at' => $announcement->published_at->toDateString().'T00:00:00',
                'ends_at' => $announcement->published_at->toDateString().'T23:59:59',
                'all_day' => true,
                'color' => '#e49a44',
            ]);
        }

        $holidays = Holiday::query()
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('date')
            ->get();
        foreach ($holidays as $holiday) {
            $isWorkingDay = $holiday->type === 'special_working_day';
            $events->push([
                'id' => 'holiday-'.$holiday->id,
                'record_id' => $holiday->id,
                'type' => 'holiday',
                'title' => $holiday->name,
                'subtitle' => $holiday->description ?: str($holiday->type)->replace('_', ' ')->title(),
                'starts_at' => $holiday->date->toDateString().'T00:00:00',
                'ends_at' => $holiday->date->toDateString().'T23:59:59',
                'all_day' => true,
                'color' => $isWorkingDay ? '#2f9e72' : '#e05d6f',
                'holiday_type' => $holiday->type,
            ]);
        }

        return response()->json([
            'data' => [
                'quote' => $this->inspiration->forToday(),
                'presence' => $this->companyPresence($timezone),
                'events' => $events->sortBy('starts_at')->values(),
                'announcements' => Announcement::query()
                    ->where('is_active', true)
                    ->orderByDesc('published_at')
                    ->limit(5)
                    ->get(['id', 'title', 'content', 'published_at', 'created_at']),
            ],
        ]);
    }

    public function analytics(DashboardAnalyticsRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->analyticsData($request->validated())]);
    }

    public function exportAnalytics(DashboardAnalyticsRequest $request): StreamedResponse
    {
        $analytics = $this->analyticsData($request->validated());
        $this->auditLogs->insertLog($request->user(), 'export dashboard analytics', $analytics['range']);

        return response()->streamDownload(function () use ($analytics): void {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, ['Metric', 'Value']);
            foreach ([
                'Headcount' => $analytics['headcount'],
                'Attendance exceptions' => $analytics['attendance_exceptions'],
                'Approved leave days' => $analytics['leave_days'],
                'Premium overtime hours' => $analytics['overtime_premium_hours'],
                'Approved payroll net' => $analytics['payroll_net'],
            ] as $metric => $value) {
                fputcsv($handle, [$metric, $value]);
            }
            fputcsv($handle, []);
            fputcsv($handle, ['Date', 'Attendance exceptions', 'Premium overtime hours']);
            foreach ($analytics['trends'] as $trend) {
                fputcsv($handle, [$trend['date'], $trend['attendance_exceptions'], $trend['overtime_hours']]);
            }
            fclose($handle);
        }, 'dashboard-analytics-'.$analytics['range']['from'].'-to-'.$analytics['range']['to'].'.csv', ['Content-Type' => 'text/csv']);
    }

    private function analyticsData(array $filters): array
    {
        $timezone = (string) $this->settings->get('organization.timezone', config('app.timezone'));
        $to = isset($filters['to']) ? Carbon::parse($filters['to'], $timezone)->endOfDay() : Carbon::now($timezone)->endOfDay();
        $from = isset($filters['from']) ? Carbon::parse($filters['from'], $timezone)->startOfDay() : $to->copy()->subDays(29)->startOfDay();
        if ($from->diffInDays($to) > 366) {
            throw ValidationException::withMessages(['to' => 'Dashboard analytics can cover at most 366 days.']);
        }

        $approvedLeave = LeaveRequest::query()->where('status', 'approved')
            ->whereDate('start_date', '<=', $to)->whereDate('end_date', '>=', $from)->get();
        $trendFrom = $from->copy()->max($to->copy()->subDays(13)->startOfDay());
        $exceptionsByDate = Attendance::query()->whereBetween('date', [$trendFrom, $to])
            ->whereNotNull('exception_codes')->get()->groupBy(fn (Attendance $attendance) => $attendance->date->toDateString())
            ->map(fn ($records) => $records->sum(fn (Attendance $attendance) => count($attendance->exception_codes ?? [])));
        $overtimeByDate = Overtime::query()->where('status', 'approved')->whereBetween('date', [$trendFrom, $to])
            ->get()->groupBy(fn (Overtime $overtime) => $overtime->date->toDateString())
            ->map(fn ($records) => round((float) $records->sum('premium_hours'), 2));

        return [
            'range' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'headcount' => Employee::query()->whereHas('user')->count(),
            'attendance_exceptions' => Attendance::query()->whereBetween('date', [$from, $to])
                ->whereNotNull('exception_codes')->get()->sum(fn (Attendance $attendance) => count($attendance->exception_codes ?? [])),
            'leave_days' => $approvedLeave->sum(fn (LeaveRequest $leave) => Carbon::parse($leave->start_date)->max($from)->diffInDays(Carbon::parse($leave->end_date)->min($to)) + 1
            ),
            'overtime_premium_hours' => (float) Overtime::query()->where('status', 'approved')
                ->whereBetween('date', [$from, $to])->sum('premium_hours'),
            'payroll_net' => (float) PayrollPeriod::query()->whereIn('status', ['approved', 'paid'])
                ->whereBetween('date_to', [$from, $to])->sum('total_net'),
            'trends' => collect(CarbonPeriod::create($trendFrom, $to))->map(function (Carbon $date) use ($exceptionsByDate, $overtimeByDate): array {
                $key = $date->toDateString();

                return [
                    'date' => $key,
                    'label' => $date->format('M j'),
                    'attendance_exceptions' => (int) ($exceptionsByDate[$key] ?? 0),
                    'overtime_hours' => (float) ($overtimeByDate[$key] ?? 0),
                ];
            })->values(),
        ];
    }

    private function companyPresence(string $timezone): array
    {
        $today = Carbon::now($timezone)->toDateString();
        $employees = Employee::query()
            ->select(['id', 'user_id', 'employee_no', 'department_id', 'position_id'])
            ->whereHas('user')
            ->with([
                'user:id,first_name,middle_name,last_name,profile_photo_path,updated_at',
                'department:id,name',
                'position:id,name',
            ])
            ->orderBy('employee_no')
            ->get();

        $employeeIds = $employees->pluck('id');
        $attendances = Attendance::query()
            ->whereDate('date', $today)
            ->whereIn('employee_id', $employeeIds)
            ->orderBy('time_in')
            ->get(['id', 'employee_id', 'date', 'time_in', 'time_out'])
            ->keyBy('employee_id');
        $employeesOnLeave = LeaveRequest::query()
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->whereIn('employee_id', $employeeIds)
            ->pluck('employee_id')
            ->flip();

        return $employees->map(function (Employee $employee) use ($attendances, $employeesOnLeave): array {
            $attendance = $attendances->get($employee->id);
            $status = match (true) {
                $attendance && $attendance->time_in && ! $attendance->time_out => 'in',
                $employeesOnLeave->has($employee->id) => 'on_leave',
                $attendance && $attendance->time_out => 'clocked_out',
                default => 'not_clocked_in',
            };

            return [
                'employee_id' => $employee->id,
                'employee_no' => $employee->employee_no,
                'user' => $employee->user?->only([
                    'id',
                    'first_name',
                    'middle_name',
                    'last_name',
                    'full_name',
                    'initials',
                    'profile_photo_url',
                ]),
                'department' => $employee->department?->name,
                'position' => $employee->position?->name,
                'status' => $status,
                'time_in' => $attendance?->time_in?->toIso8601String(),
                'time_out' => $attendance?->time_out?->toIso8601String(),
            ];
        })->values()->all();
    }
}
