<?php

namespace App\Http\Controllers\Training;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTrainingCourseRequest;
use App\Http\Requests\StoreTrainingEnrollmentRequest;
use App\Models\TrainingCourse;
use App\Models\TrainingEnrollment;
use App\Services\AuditLog\AuditLogServiceInterface;

class TrainingController extends Controller
{
    private AuditLogServiceInterface $auditLogs;

    public function __construct(AuditLogServiceInterface $auditLogs)
    {
        $this->auditLogs = $auditLogs;
        $this->middleware('permission:manage-employees');
    }

    public function index()
    {
        return response()->json(['data' => TrainingCourse::query()->latest()->get()]);
    }

    public function store(StoreTrainingCourseRequest $request)
    {
        $course = TrainingCourse::query()->create($request->validated());
        $this->auditLogs->insertLog($course, 'create training course');

        return response()->json(['message' => 'Training course created successfully.', 'data' => $course], 201);
    }

    public function enroll(StoreTrainingEnrollmentRequest $request, TrainingCourse $course)
    {
        $enrollment = TrainingEnrollment::query()->firstOrCreate(['course_id' => $course->getKey(), 'employee_id' => $request->validated('employee_id')], ['status' => 'enrolled', 'certificate_expires_on' => $request->validated('certificate_expires_on')]);
        $this->auditLogs->insertLog($enrollment, 'enroll employee in training');

        return response()->json(['message' => 'Training enrollment created successfully.', 'data' => $enrollment], 201);
    }

    public function complete(TrainingEnrollment $enrollment)
    {
        $enrollment->update(['status' => 'completed', 'completed_on' => now()->toDateString()]);
        $this->auditLogs->insertLog($enrollment, 'complete training enrollment');

        return response()->json(['message' => 'Training enrollment completed successfully.', 'data' => $enrollment->fresh()], 202);
    }
}
