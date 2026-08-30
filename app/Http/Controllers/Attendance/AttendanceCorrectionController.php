<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceCorrectionRequest;
use App\Http\Requests\ReviewAttendanceCorrectionRequest;
use App\Models\AttendanceCorrectionRequest as Correction;
use App\Services\Approvals\DelegatedApproverResolver;
use App\Services\Attendance\AttendanceCorrectionService;
use App\Services\Utils\ResponseServiceInterface;
use Illuminate\Http\Request;

class AttendanceCorrectionController extends Controller
{
    private AttendanceCorrectionService $service;

    private ResponseServiceInterface $response;

    private DelegatedApproverResolver $delegates;

    public function __construct(
        AttendanceCorrectionService $service,
        ResponseServiceInterface $response,
        DelegatedApproverResolver $delegates
    ) {
        $this->service = $service;
        $this->response = $response;
        $this->delegates = $delegates;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $query = Correction::query()->with(['employee.user', 'attendance'])->latest();
        if (! $user->hasAnyPermission(['view-attendance-corrections', 'approve-attendance-corrections'])) {
            $query->where('employee_id', $user->employee?->id);
        }

        return $this->response->successResponse('Attendance correction requests', $query->paginate($request->integer('limit', 20)));
    }

    public function store(AttendanceCorrectionRequest $request)
    {
        return $this->service->submit($request->validated(), $request->user()->employee?->id ?? '');
    }

    public function review(ReviewAttendanceCorrectionRequest $request, Correction $attendanceCorrection)
    {
        abort_unless($this->delegates->canApprove($request->user(), 'approve-attendance-corrections'), 403);

        return $this->service->review($attendanceCorrection, $request->validated(), $request->user()->id);
    }
}
