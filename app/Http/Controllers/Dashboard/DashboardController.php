<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Models\WorkplaceMeeting;
use App\Services\AppSettings\AppSettingService;
use App\Services\Dashboard\DailyInspirationService;
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

        if ($user->hasPermission('view-workplace-hub')) {
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

        return response()->json([
            'data' => [
                'quote' => $this->inspiration->forToday(),
                'events' => $events->sortBy('starts_at')->values(),
                'announcements' => Announcement::query()
                    ->where('is_active', true)
                    ->orderByDesc('published_at')
                    ->limit(5)
                    ->get(['id', 'title', 'content', 'published_at', 'created_at']),
            ],
        ]);
    }
}
