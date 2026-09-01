<?php

namespace App\Http\Controllers\Benefits;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBenefitEnrollmentRequest;
use App\Http\Requests\StoreBenefitPlanRequest;
use App\Models\BenefitEnrollment;
use App\Models\BenefitPlan;
use App\Services\AuditLog\AuditLogServiceInterface;

class BenefitController extends Controller
{
    private AuditLogServiceInterface $auditLogs;

    public function __construct(AuditLogServiceInterface $auditLogs)
    {
        $this->auditLogs = $auditLogs;
        $this->middleware('permission:manage-employees');
    }

    public function index()
    {
        return response()->json(['data' => BenefitPlan::query()->latest()->get()]);
    }

    public function store(StoreBenefitPlanRequest $request)
    {
        $plan = BenefitPlan::query()->create($request->validated());
        $this->auditLogs->insertLog($plan, 'create benefit plan');

        return response()->json(['message' => 'Benefit plan created successfully.', 'data' => $plan], 201);
    }

    public function enroll(StoreBenefitEnrollmentRequest $request, BenefitPlan $plan)
    {
        abort_unless($plan->is_active, 409, 'This benefit plan is inactive.');
        $enrollment = BenefitEnrollment::query()->firstOrCreate(['benefit_plan_id' => $plan->getKey(), 'employee_id' => $request->validated('employee_id')], [...$request->validated(), 'status' => 'active']);
        $this->auditLogs->insertLog($enrollment, 'enroll employee in benefit plan');

        return response()->json(['message' => 'Benefit enrollment created successfully.', 'data' => $enrollment], 201);
    }
}
