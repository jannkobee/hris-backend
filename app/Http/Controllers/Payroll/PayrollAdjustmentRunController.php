<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePayrollAdjustmentItemRequest;
use App\Http\Requests\StorePayrollAdjustmentRunRequest;
use App\Models\PayrollAdjustmentRun;
use App\Services\AuditLog\AuditLogServiceInterface;
use App\Services\Utils\ResponseServiceInterface;
use Illuminate\Http\Request;

class PayrollAdjustmentRunController extends Controller
{
    private AuditLogServiceInterface $auditLogs;

    private ResponseServiceInterface $response;

    public function __construct(AuditLogServiceInterface $auditLogs, ResponseServiceInterface $response)
    {
        $this->auditLogs = $auditLogs;
        $this->response = $response;
        $this->middleware('permission:manage-payroll');
    }

    public function index()
    {
        return $this->response->successResponse('Payroll adjustment runs', PayrollAdjustmentRun::query()->with(['period', 'items.employee.user'])->latest()->get());
    }

    public function store(StorePayrollAdjustmentRunRequest $request)
    {
        $run = PayrollAdjustmentRun::query()->create([...$request->validated(), 'created_by' => $request->user()->getKey(), 'status' => 'draft']);
        $this->auditLogs->insertLog($run, 'create payroll adjustment run');

        return $this->response->storeResponse('Payroll adjustment run', $run);
    }

    public function storeItem(StorePayrollAdjustmentItemRequest $request, PayrollAdjustmentRun $run)
    {
        abort_if($run->isLocked(), 409, 'Locked adjustment runs cannot be changed.');
        $item = $run->items()->create($request->validated());
        $this->auditLogs->insertLog($item, 'create payroll adjustment item');

        return $this->response->storeResponse('Payroll adjustment item', $item);
    }

    public function lock(Request $request, PayrollAdjustmentRun $run)
    {
        abort_if($run->isLocked(), 409, 'This adjustment run is already locked.');
        $run->update(['status' => 'locked', 'locked_at' => now(), 'locked_by' => $request->user()->getKey()]);
        $this->auditLogs->insertLog($run, 'lock payroll adjustment run');

        return $this->response->updateResponse('Payroll adjustment run', $run->fresh());
    }
}
