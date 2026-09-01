<?php

namespace App\Http\Controllers\Approval;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreApprovalWorkflowRequest;
use App\Http\Requests\StoreApprovalWorkflowStepRequest;
use App\Models\ApprovalWorkflow;
use App\Services\AuditLog\AuditLogServiceInterface;
use App\Services\Utils\ResponseServiceInterface;

class ApprovalWorkflowController extends Controller
{
    private AuditLogServiceInterface $auditLogs;

    private ResponseServiceInterface $response;

    public function __construct(AuditLogServiceInterface $auditLogs, ResponseServiceInterface $response)
    {
        $this->auditLogs = $auditLogs;
        $this->response = $response;
        $this->middleware('permission:manage-organization-settings');
    }

    public function index()
    {
        return $this->response->successResponse('Approval workflows', ApprovalWorkflow::query()->with('steps')->latest()->get());
    }

    public function store(StoreApprovalWorkflowRequest $request)
    {
        $workflow = ApprovalWorkflow::query()->create($request->validated());
        $this->auditLogs->insertLog($workflow, 'create approval workflow');

        return $this->response->storeResponse('Approval workflow', $workflow);
    }

    public function storeStep(StoreApprovalWorkflowStepRequest $request, ApprovalWorkflow $workflow)
    {
        $step = $workflow->steps()->create($request->validated());
        $this->auditLogs->insertLog($step, 'create approval workflow step');

        return $this->response->storeResponse('Approval workflow step', $step);
    }
}
