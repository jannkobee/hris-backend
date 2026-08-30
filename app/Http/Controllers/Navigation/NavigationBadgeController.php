<?php

namespace App\Http\Controllers\Navigation;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\AttendanceCorrectionRequest;
use App\Models\LeaveConversionRequest;
use App\Models\LeaveRequest;
use App\Models\MeetingActionItem;
use App\Models\Overtime;
use App\Models\PayrollPeriod;
use App\Models\User;
use App\Services\Plans\PlanEntitlementService;
use App\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NavigationBadgeController extends Controller
{
    private PlanEntitlementService $planEntitlements;

    public function __construct(PlanEntitlementService $planEntitlements)
    {
        $this->planEntitlements = $planEntitlements;
    }

    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $badges = [
            'messages' => $this->unreadMessages($user->id),
            'notifications' => AppNotification::query()->where('user_id', $user->id)->whereNull('read_at')->count(),
        ];

        if ($user->hasPermission('approve-leave-requests')) {
            $badges['leave-management'] = LeaveRequest::query()->where('status', 'pending')->count();
        }

        if ($user->hasPermission('approve-leave-conversion-requests')) {
            $badges['leave-management'] = ($badges['leave-management'] ?? 0)
                + LeaveConversionRequest::query()->where('status', 'pending')->count();
        }

        if ($user->hasPermission('approve-overtimes')) {
            $badges['overtime-management'] = Overtime::query()->where('status', 'pending')->count();
        }

        if ($user->hasPermission('approve-attendance-corrections')) {
            $badges['approval-inbox'] = AttendanceCorrectionRequest::query()->where('status', 'pending')->count();
        }

        if ($this->planEntitlements->allows($user->organization, 'payroll')
            && $user->hasAnyPermission(['approve-payroll', 'mark-payroll-paid'])) {
            $statuses = [];
            if ($user->hasPermission('approve-payroll')) {
                $statuses[] = 'processed';
            }
            if ($user->hasPermission('mark-payroll-paid')) {
                $statuses[] = 'approved';
            }
            $badges['payroll-management'] = PayrollPeriod::query()->whereIn('status', $statuses)->count();
        }

        if ($this->planEntitlements->allows($user->organization, 'workplace_hub')
            && $user->hasPermission('view-workplace-hub')) {
            $badges['workplace-hub'] = MeetingActionItem::query()
                ->where('assigned_to', $user->id)
                ->where('status', '!=', 'completed')
                ->count();
        }

        return response()->json(['data' => $badges]);
    }

    private function unreadMessages(string $userId): int
    {
        $organizationId = app(TenantContext::class)->id();

        return DB::table('messages')
            ->join('conversation_participants', function ($join) use ($userId, $organizationId): void {
                $join->on('conversation_participants.conversation_id', '=', 'messages.conversation_id')
                    ->where('conversation_participants.user_id', '=', $userId)
                    ->where('conversation_participants.organization_id', '=', $organizationId);
            })
            ->where('messages.organization_id', $organizationId)
            ->where('messages.sender_id', '!=', $userId)
            ->where(function ($query): void {
                $query->whereNull('conversation_participants.last_read_at')
                    ->orWhereColumn('messages.created_at', '>', 'conversation_participants.last_read_at');
            })
            ->count();
    }
}
