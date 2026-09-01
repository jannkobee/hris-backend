<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeLifecycleChecklistRequest;
use App\Http\Requests\StoreEmployeeLifecycleTaskRequest;
use App\Models\EmployeeLifecycleChecklist;
use App\Models\EmployeeLifecycleTask;
use App\Services\AuditLog\AuditLogServiceInterface;
use App\Services\Utils\ResponseServiceInterface;
use Illuminate\Http\Request;

class EmployeeLifecycleController extends Controller
{
    private AuditLogServiceInterface $auditLogs;

    private ResponseServiceInterface $response;

    public function __construct(AuditLogServiceInterface $auditLogs, ResponseServiceInterface $response)
    {
        $this->auditLogs = $auditLogs;
        $this->response = $response;
        $this->middleware('permission:manage-employees');
    }

    public function index()
    {
        return $this->response->successResponse('Employee lifecycle checklists', EmployeeLifecycleChecklist::query()->with(['tasks.employee.user', 'tasks.owner'])->latest()->get());
    }

    public function storeChecklist(StoreEmployeeLifecycleChecklistRequest $request)
    {
        $checklist = EmployeeLifecycleChecklist::query()->create($request->validated());
        $this->auditLogs->insertLog($checklist, 'create employee lifecycle checklist');

        return $this->response->storeResponse('Employee lifecycle checklist', $checklist);
    }

    public function storeTask(StoreEmployeeLifecycleTaskRequest $request, EmployeeLifecycleChecklist $checklist)
    {
        $task = $checklist->tasks()->create($request->validated());
        $this->auditLogs->insertLog($task, 'create employee lifecycle task');

        return $this->response->storeResponse('Employee lifecycle task', $task);
    }

    public function complete(Request $request, EmployeeLifecycleTask $task)
    {
        $task->update(['completed_at' => now(), 'completed_by' => $request->user()->getKey()]);
        $this->auditLogs->insertLog($task, 'complete employee lifecycle task');

        return $this->response->updateResponse('Employee lifecycle task', $task->fresh());
    }
}
