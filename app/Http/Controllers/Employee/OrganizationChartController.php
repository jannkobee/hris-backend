<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Services\Utils\ResponseServiceInterface;
use Illuminate\Http\JsonResponse;

class OrganizationChartController extends Controller
{
    public function __construct(private readonly ResponseServiceInterface $response)
    {
        $this->middleware('permission:view-employees');
    }

    public function __invoke(): JsonResponse
    {
        $employees = Employee::query()
            ->with([
                'user:id,first_name,middle_name,last_name,email,profile_photo_disk,profile_photo_path,profile_photo_name',
                'department:id,name',
                'position:id,name',
                'jobGrade:id,name',
            ])
            ->get();

        $byManager = [];
        $roots = [];

        foreach ($employees as $employee) {
            $managerId = $employee->manager_id;
            if (! $managerId) {
                $roots[] = $employee;
            } else {
                $byManager[$managerId][] = $employee;
            }
        }

        $buildTree = function (Employee $employee, array $visited = []) use (&$buildTree, &$byManager): array {
            $id = $employee->id;
            if (in_array($id, $visited, true)) {
                return $this->formatNode($employee, []);
            }
            $visited[] = $id;

            $children = [];
            foreach ($byManager[$id] ?? [] as $report) {
                $children[] = $buildTree($report, $visited);
            }

            return $this->formatNode($employee, $children);
        };

        if (empty($roots) && $employees->isNotEmpty()) {
            $employeeIds = $employees->pluck('id')->all();
            foreach ($employees as $employee) {
                if (! in_array($employee->manager_id, $employeeIds, true)) {
                    $roots[] = $employee;
                }
            }
        }

        $tree = [];
        foreach ($roots as $root) {
            $tree[] = $buildTree($root);
        }

        return $this->response->successResponse('Organization chart', [
            'total_employees' => $employees->count(),
            'tree' => $tree,
        ]);
    }

    private function formatNode(Employee $employee, array $children): array
    {
        return [
            'id' => $employee->id,
            'employee_no' => $employee->employee_no,
            'user' => [
                'id' => $employee->user?->id,
                'name' => trim(($employee->user?->first_name ?? '').' '.($employee->user?->last_name ?? '')),
                'email' => $employee->user?->email,
                'photo_url' => $employee->user?->profile_photo_url,
            ],
            'department' => $employee->department ? [
                'id' => $employee->department->id,
                'name' => $employee->department->name,
            ] : null,
            'position' => $employee->position ? [
                'id' => $employee->position->id,
                'name' => $employee->position->name,
            ] : null,
            'job_grade' => $employee->jobGrade ? [
                'id' => $employee->jobGrade->id,
                'name' => $employee->jobGrade->name,
            ] : null,
            'direct_reports_count' => count($children),
            'children' => $children,
        ];
    }
}
