<?php

namespace App\Http\Controllers\ScheduledTask;

use App\Http\Controllers\Controller;
use App\Http\Requests\ScheduledTaskRequest as ModelRequest;
use App\Models\ScheduledTask;
use App\Repository\ScheduledTask\ScheduledTaskRepositoryInterface;
use Illuminate\Support\Facades\Artisan;

class ScheduledTaskController extends Controller
{
    private ScheduledTaskRepositoryInterface $modelRepository;

    public function __construct(ScheduledTaskRepositoryInterface $modelRepository)
    {
        $this->modelRepository = $modelRepository;
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
    public function runNow(string $id)
    {
        $task = ScheduledTask::findOrFail($id);

        $exitCode = Artisan::call($task->command);
        $output = Artisan::output();

        $task->update([
            'last_run_at' => now(),
            'last_run_output' => $output ?: ($exitCode === 0 ? 'Success' : 'Failed'),
        ]);

        return response()->json([
            'message' => $exitCode === 0
                ? 'Task executed successfully.'
                : 'Task failed. See output for details.',
            'exit_code' => $exitCode,
            'output' => $output,
        ], $exitCode === 0 ? 200 : 422);
    }
}
