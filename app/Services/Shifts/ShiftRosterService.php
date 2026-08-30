<?php

namespace App\Services\Shifts;

use App\Models\ShiftAssignment;
use App\Models\ShiftTemplate;
use App\Services\AuditLog\AuditLogServiceInterface;
use App\Services\Utils\ResponseServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ShiftRosterService
{
    public function __construct(
        private readonly ResponseServiceInterface $response,
        private readonly AuditLogServiceInterface $audit,
    ) {
    }

    public function listAssignments(array $filters): JsonResponse
    {
        $query = ShiftAssignment::query()->with(['employee.user', 'shiftTemplate']);

        if (filled($filters['from'] ?? null)) {
            $query->whereDate('work_date', '>=', $filters['from']);
        }
        if (filled($filters['to'] ?? null)) {
            $query->whereDate('work_date', '<=', $filters['to']);
        }
        if (filled($filters['employee_id'] ?? null)) {
            $query->where('employee_id', $filters['employee_id']);
        }

        return $this->response->successResponse('Shift assignments', $query->orderBy('work_date')->paginate((int) ($filters['limit'] ?? 50)));
    }

    public function createAssignment(array $attributes): JsonResponse
    {
        $assignment = $this->persistAssignment(new ShiftAssignment(), $attributes);

        return $this->response->storeResponse('Shift assignment', $assignment);
    }

    public function updateAssignment(ShiftAssignment $assignment, array $attributes): JsonResponse
    {
        $before = $assignment->toArray();
        $assignment = $this->persistAssignment($assignment, $attributes);
        $this->audit->insertLog($assignment, 'update', ['record_id' => $assignment->id, 'before' => $before, 'after' => $assignment->toArray()]);

        return $this->response->updateResponse('Shift assignment', $assignment);
    }

    public function deleteAssignment(ShiftAssignment $assignment): JsonResponse
    {
        $snapshot = $assignment->toArray();
        $id = $assignment->id;
        $assignment->delete();
        $this->audit->insertLog(new ShiftAssignment(), 'delete', ['record_id' => $id, 'before' => $snapshot]);

        return $this->response->deleteResponse('Shift assignment', true);
    }

    private function persistAssignment(ShiftAssignment $assignment, array $attributes): ShiftAssignment
    {
        $creating = ! $assignment->exists;
        $template = ShiftTemplate::query()->find($attributes['shift_template_id']);
        if (! $template || ! $template->is_active) {
            throw ValidationException::withMessages(['shift_template_id' => 'Select an active shift template.']);
        }

        $assignment->fill([
            'employee_id' => $attributes['employee_id'],
            'shift_template_id' => $template->id,
            'work_date' => $attributes['work_date'],
            'shift_name' => $template->name,
            'start_time' => $template->start_time,
            'end_time' => $template->end_time,
            'break_minutes' => $template->break_minutes,
            'grace_minutes' => $template->grace_minutes,
            'notes' => $attributes['notes'] ?? null,
        ]);
        $assignment->save();
        $assignment->refresh()->load(['employee.user', 'shiftTemplate']);

        if (! $creating) {
            return $assignment;
        }

        $this->audit->insertLog($assignment, 'create', ['record_id' => $assignment->id, 'after' => $assignment->toArray()]);

        return $assignment;
    }
}
