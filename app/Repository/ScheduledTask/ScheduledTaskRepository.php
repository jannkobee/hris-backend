<?php

namespace App\Repository\ScheduledTask;

use App\Models\ScheduledTask;
use App\Repository\Base\BaseRepository;
use App\Services\AuditLog\AuditLogServiceInterface;
use App\Services\Utils\ResponseServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ScheduledTaskRepository extends BaseRepository implements ScheduledTaskRepositoryInterface
{
    public function __construct(ScheduledTask $model, ResponseServiceInterface $responseService, AuditLogServiceInterface $auditLogService)
    {
        parent::__construct($model, $responseService, $auditLogService);
    }

    public function create(array $attributes): JsonResponse
    {
        $this->ensureNotAutoManagedName($attributes['name'] ?? '');

        return parent::create($attributes);
    }

    public function update(array $attributes, string|int $id): JsonResponse
    {
        $task = $this->model->find($id);
        if (! $task) {
            return parent::update($attributes, $id);
        }

        if (in_array($task->name, ScheduledTask::AUTO_MANAGED_NAMES, true) && array_intersect(array_keys($attributes), [
            'name', 'command', 'frequency', 'run_time', 'run_days', 'run_day_of_month', 'run_months', 'cron_expression', 'is_active',
        ])) {
            throw ValidationException::withMessages([
                'scheduled_task' => 'This task is managed by Leave Credit Settings and cannot be changed here.',
            ]);
        }

        return parent::update($attributes, $id);
    }

    public function delete(string $id): JsonResponse
    {
        $task = $this->model->find($id);
        if ($task && in_array($task->name, ScheduledTask::AUTO_MANAGED_NAMES, true)) {
            throw ValidationException::withMessages([
                'scheduled_task' => 'This task is managed by Leave Credit Settings and cannot be deleted.',
            ]);
        }

        return parent::delete($id);
    }

    private function ensureNotAutoManagedName(string $name): void
    {
        if (in_array($name, ScheduledTask::AUTO_MANAGED_NAMES, true)) {
            throw ValidationException::withMessages([
                'name' => 'This scheduled task is created automatically by Leave Credit Settings.',
            ]);
        }
    }
}
