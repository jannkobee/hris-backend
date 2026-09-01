<?php

namespace App\Http\Controllers\Performance;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePerformanceGoalRequest;
use App\Models\PerformanceGoal;
use App\Services\AuditLog\AuditLogServiceInterface;

class PerformanceGoalController extends Controller
{
    private AuditLogServiceInterface $auditLogs;

    public function __construct(AuditLogServiceInterface $auditLogs)
    {
        $this->auditLogs = $auditLogs;
        $this->middleware('permission:manage-employees');
    }

    public function index()
    {
        return response()->json(['data' => PerformanceGoal::query()->latest()->get()]);
    }

    public function store(StorePerformanceGoalRequest $request)
    {
        $goal = PerformanceGoal::query()->create([...$request->validated(), 'owner_id' => $request->user()->getKey()]);
        $this->auditLogs->insertLog($goal, 'create performance goal');

        return response()->json(['message' => 'Performance goal created successfully.', 'data' => $goal], 201);
    }
}
