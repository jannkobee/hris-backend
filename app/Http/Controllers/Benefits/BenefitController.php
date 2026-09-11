<?php

namespace App\Http\Controllers\Benefits;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBenefitEnrollmentRequest;
use App\Http\Requests\StoreBenefitPlanRequest;
use App\Http\Requests\UpdateBenefitPlanRequest;
use App\Models\BenefitEnrollment;
use App\Models\BenefitPlan;
use App\Services\AuditLog\AuditLogServiceInterface;
use App\Services\Benefits\PhilippineDeMinimisService;

class BenefitController extends Controller
{
    private AuditLogServiceInterface $auditLogs;

    public function __construct(AuditLogServiceInterface $auditLogs)
    {
        $this->auditLogs = $auditLogs;
        $this->middleware('permission:manage-employees')->except(['deMinimisCeilings']);
    }

    public function index()
    {
        return response()->json([
            'data' => BenefitPlan::query()->withCount('enrollments')->latest()->get(),
        ]);
    }

    public function store(StoreBenefitPlanRequest $request)
    {
        $plan = BenefitPlan::query()->create($request->validated());
        $this->auditLogs->insertLog($plan, 'create benefit plan');

        return response()->json([
            'message' => 'Benefit plan created successfully.',
            'data' => $plan->loadCount('enrollments'),
        ], 201);
    }

    public function update(UpdateBenefitPlanRequest $request, BenefitPlan $plan)
    {
        $plan->update($request->validated());
        $this->auditLogs->insertLog($plan, 'update benefit plan');

        return response()->json([
            'message' => 'Benefit plan updated successfully.',
            'data' => $plan->fresh()->loadCount('enrollments'),
        ]);
    }

    public function enroll(StoreBenefitEnrollmentRequest $request, BenefitPlan $plan)
    {
        abort_unless($plan->is_active, 409, 'This benefit plan is inactive.');
        $enrollment = BenefitEnrollment::query()->firstOrCreate(
            ['benefit_plan_id' => $plan->getKey(), 'employee_id' => $request->validated('employee_id')],
            [...$request->validated(), 'status' => 'active']
        );
        $this->auditLogs->insertLog($enrollment, 'enroll employee in benefit plan');

        return response()->json([
            'message' => 'Benefit enrollment created successfully.',
            'data' => $enrollment->load('employee.user'),
        ], 201);
    }

    public function enrollments(BenefitPlan $plan)
    {
        $enrollments = $plan->enrollments()
            ->with([
                'employee.user:id,first_name,last_name,email',
                'employee.department:id,name',
                'employee.position:id,name',
            ])
            ->latest()
            ->get();

        return response()->json(['data' => $enrollments]);
    }

    public function cancelEnrollment(BenefitEnrollment $enrollment)
    {
        $enrollment->update([
            'status' => 'cancelled',
            'effective_to' => now()->toDateString(),
        ]);
        $this->auditLogs->insertLog($enrollment, 'cancel employee benefit enrollment');

        return response()->json([
            'message' => 'Benefit enrollment cancelled successfully.',
            'data' => $enrollment->fresh()->load('employee.user'),
        ]);
    }

    public function deMinimisCeilings(PhilippineDeMinimisService $deMinimisService)
    {
        return response()->json([
            'data' => $deMinimisService->getCeilings(),
        ]);
    }
}
