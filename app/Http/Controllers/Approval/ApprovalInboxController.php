<?php

namespace App\Http\Controllers\Approval;

use App\Http\Controllers\Controller;
use App\Models\AttendanceCorrectionRequest;
use App\Models\LeaveRequest;
use App\Models\Overtime;
use App\Models\User;
use App\Services\Utils\ResponseServiceInterface;
use Illuminate\Http\Request;

class ApprovalInboxController extends Controller
{
    private ResponseServiceInterface $response;

    public function __construct(ResponseServiceInterface $response)
    {
        $this->response = $response;
    }

    public function index(Request $request)
    {
        /** @var User $user */ $user = $request->user();
        $items = collect();
        if ($user->hasPermission('approve-attendance-corrections')) {
            $items = $items->merge(AttendanceCorrectionRequest::query()->with('employee.user')->where('status', 'pending')->get()->map(fn ($item) => ['id' => $item->id, 'type' => 'attendance_correction', 'employee' => $item->employee?->user?->full_name, 'submitted_at' => $item->created_at, 'summary' => $item->reason]));
        }
        if ($user->hasPermission('approve-leave-requests')) {
            $items = $items->merge(LeaveRequest::query()->with('employee.user', 'leaveType')->where('status', 'pending')->get()->map(fn ($item) => ['id' => $item->id, 'type' => 'leave', 'employee' => $item->employee?->user?->full_name, 'submitted_at' => $item->created_at, 'summary' => ($item->leaveType?->name ?? 'Leave').' · '.$item->start_date?->toDateString()]));
        }
        if ($user->hasPermission('approve-overtimes')) {
            $items = $items->merge(Overtime::query()->with('employee.user')->where('status', 'pending')->get()->map(fn ($item) => ['id' => $item->id, 'type' => 'overtime', 'employee' => $item->employee?->user?->full_name, 'submitted_at' => $item->created_at, 'summary' => $item->date?->toDateString().' · '.$item->hours.' hours']));
        }

        return $this->response->successResponse('Approval inbox', $items->sortBy('submitted_at')->values());
    }
}
