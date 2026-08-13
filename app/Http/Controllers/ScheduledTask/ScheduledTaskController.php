<?php

namespace App\Http\Controllers\ScheduledTask;

use App\Http\Controllers\Controller;
use App\Http\Requests\ScheduledTaskRequest as ModelRequest;
use App\Models\ScheduledTask;
use App\Repository\ScheduledTask\ScheduledTaskRepositoryInterface;
use App\Services\Scheduling\ScheduledTaskScheduleService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class ScheduledTaskController extends Controller
{
    private ScheduledTaskRepositoryInterface $modelRepository;

    public function __construct(ScheduledTaskRepositoryInterface $modelRepository)
    {
        $this->modelRepository = $modelRepository;
        $this->middleware('permission:view-scheduled-tasks')->only(['index', 'show']);
        $this->middleware('permission:manage-scheduled-tasks')->only(['store', 'update', 'destroy']);
        $this->middleware('permission:run-scheduled-tasks')->only('runNow');
    }

    public function index()
    {
        return $this->modelRepository->getList();
    }

    public function store(ModelRequest $request)
    {
        return $this->modelRepository->create($request->validated());
    }

    public function show(string $id)
    {
        return $this->modelRepository->find($id);
    }

    public function update(ModelRequest $request, string $id)
    {
        return $this->modelRepository->update($request->validated(), $id);
    }

    public function destroy(string $id)
    {
        return $this->modelRepository->delete($id);
    }

    /**
     * Trigger a task's command immediately, outside of its normal schedule
     * (the "Run now" button in Scheduled Task Management).
     */
    public function runNow(string $id, ScheduledTaskScheduleService $scheduleService)
    {
        $task = ScheduledTask::findOrFail($id);
        $lock = Cache::lock("scheduled-task:{$task->id}", 3600);

        if (! $lock->get()) {
            return response()->json([
                'message' => 'This task is already running.',
            ], 409);
        }

        try {
            $exitCode = Artisan::call($task->command);
            $output = Artisan::output();

            $task->update([
                'last_run_at' => now(),
                'last_run_output' => $output ?: ($exitCode === 0 ? 'Success' : 'Failed'),
                'next_run_at' => $task->is_active ? $scheduleService->nextRunAt($task) : null,
            ]);

            return response()->json([
                'message' => $exitCode === 0
                    ? 'Task executed successfully.'
                    : 'Task failed. See output for details.',
                'exit_code' => $exitCode,
                'output' => $output,
            ], $exitCode === 0 ? 200 : 422);
        } finally {
            $lock->release();
        }
    }
}
