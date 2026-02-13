<?php

namespace App\Repository\Employee;

use App\Models\Employee;
use App\Repository\Base\BaseRepository;
use App\Services\AuditLog\AuditLogServiceInterface;
use App\Services\Utils\ResponseServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;

class EmployeeRepository extends BaseRepository implements EmployeeRepositoryInterface
{
    public function __construct(
        Employee $model,
        ResponseServiceInterface $responseService,
        AuditLogServiceInterface $auditLogService
    ) {
        parent::__construct($model, $responseService, $auditLogService);
    }

    public function create(array $attributes): JsonResponse
    {
        // Normalize nested objects -> *_id
        $attributes['user_id'] = $attributes['user_id'] ?? data_get($attributes, 'user.id');

        $attributes['employment_status_id'] =
            $attributes['employment_status_id'] ?? data_get($attributes, 'employmentStatus.id');

        $attributes['department_id'] =
            $attributes['department_id'] ?? data_get($attributes, 'department.id');

        $attributes['position_id'] =
            $attributes['position_id'] ?? data_get($attributes, 'position.id');

        $attributes['job_grade_id'] =
            $attributes['job_grade_id'] ?? data_get($attributes, 'jobGrade.id');

        // IMPORTANT: if FE forgets to set it, backend still protects
        $attributes['employee_no'] =
            $attributes['employee_no'] ?? $this->makeUniqueEmployeeNo();

        // Remove nested keys
        $attributes = Arr::except($attributes, [
            'user',
            'employmentStatus',
            'department',
            'position',
            'jobGrade',
        ]);

        // Keep only fillable fields
        $attributes = Arr::only($attributes, $this->model->getFillable());

        return parent::create($attributes);
    }

    public function generateEmployeeNo(): JsonResponse
    {
        return $this->responseService->resolveResponse(
            "Employee Number generated successfully",
            ['employee_no' => $this->makeUniqueEmployeeNo()]
        );
    }

    private function makeUniqueEmployeeNo(): string
    {
        $year = now()->format('Y');

        do {
            $rand = random_int(100000, 999999);
            $employeeNo = "EMP-{$year}-{$rand}";
        } while (Employee::where('employee_no', $employeeNo)->exists());

        return $employeeNo;
    }
}
