<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Models\WorkplaceMeeting;
use App\Services\AppSettings\AppSettingService;
use App\Services\Dashboard\DailyInspirationService;
use App\Services\Plans\PlanEntitlementService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DashboardController extends Controller
{
    public function __construct(
        private readonly AppSettingService $settings,
        private readonly DailyInspirationService $inspiration,
        private readonly PlanEntitlementService $planEntitlements,
    ) {
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
