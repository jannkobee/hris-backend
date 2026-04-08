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

        // Extract addresses and contacts before creating employee
        $addresses = $attributes['addresses'] ?? [];
        $contacts = $attributes['contacts'] ?? [];

        // Remove nested keys
        $attributes = Arr::except($attributes, [
            'user',
            'employmentStatus',
            'department',
            'position',
            'jobGrade',
            'addresses',
            'contacts',
        ]);

        // Keep only fillable fields
        $attributes = Arr::only($attributes, $this->model->getFillable());

        // Create employee
        $response = parent::create($attributes);
        $responseData = json_decode($response->getContent(), true);

        if ($response->isSuccessful() && isset($responseData['data']['id'])) {
            $employeeId = $responseData['data']['id'];
            $employee = Employee::find($employeeId);

            // Sync addresses
            if (!empty($addresses)) {
                $this->syncAddresses($employee, $addresses);
            }

            // Sync contacts
            if (!empty($contacts)) {
                $this->syncContacts($employee, $contacts);
            }

            // Reload employee with relationships
            $employee->load(['addresses', 'contacts']);

            return $this->responseService->resolveResponse(
                "Employee created successfully",
                $employee
            );
        }

        return $response;
    }

    public function update(array $attributes, $id): JsonResponse
    {
        // Normalize nested objects -> *_id (supports your Form payload shape)
        $attributes['user_id'] = $attributes['user_id'] ?? data_get($attributes, 'user.id');

        $attributes['employment_status_id'] =
            $attributes['employment_status_id'] ?? data_get($attributes, 'employmentStatus.id');

        $attributes['department_id'] =
            $attributes['department_id'] ?? data_get($attributes, 'department.id');

        $attributes['position_id'] =
            $attributes['position_id'] ?? data_get($attributes, 'position.id');

        $attributes['job_grade_id'] =
            $attributes['job_grade_id'] ?? data_get($attributes, 'jobGrade.id');

        // Do NOT allow employee_no to be cleared/overwritten on update unless explicitly provided
        if (!array_key_exists('employee_no', $attributes) || blank($attributes['employee_no'])) {
            unset($attributes['employee_no']);
        }

        // Extract addresses and contacts before updating employee
        $addresses = $attributes['addresses'] ?? null;
        $contacts = $attributes['contacts'] ?? null;

        // Remove nested keys that should NOT be stored directly
        $attributes = Arr::except($attributes, [
            'user',
            'employmentStatus',
            'department',
            'position',
            'jobGrade',
            'addresses',
            'contacts',
        ]);

        // Keep only fillable fields
        $attributes = Arr::only($attributes, $this->model->getFillable());

        // Update employee
        $response = parent::update($attributes, $id);

        if ($response->isSuccessful()) {
            $employee = Employee::find($id);

            // Sync addresses if provided
            if ($addresses !== null) {
                $this->syncAddresses($employee, $addresses);
            }

            // Sync contacts if provided
            if ($contacts !== null) {
                $this->syncContacts($employee, $contacts);
            }

            // Reload employee with relationships
            $employee->load(['addresses', 'contacts']);

            return $this->responseService->resolveResponse(
                "Employee updated successfully",
                $employee
            );
        }

        return $response;
    }

    /**
     * Sync employee addresses
     */
    private function syncAddresses(Employee $employee, array $addresses): void
    {
        $addressIds = [];

        foreach ($addresses as $addressData) {
            // If address has an ID, update it; otherwise create new
            if (!empty($addressData['id'])) {
                $address = $employee->addresses()->find($addressData['id']);
                if ($address) {
                    $address->update(Arr::except($addressData, ['id', 'employee_id']));
                    $addressIds[] = $address->id;
                }
            } else {
                $address = $employee->addresses()->create(
                    Arr::except($addressData, ['id', 'employee_id'])
                );
                $addressIds[] = $address->id;
            }
        }

        // Delete addresses that are not in the submitted list
        $employee->addresses()->whereNotIn('id', $addressIds)->delete();
    }

    /**
     * Sync employee contacts
     */
    private function syncContacts(Employee $employee, array $contacts): void
    {
        $contactIds = [];

        foreach ($contacts as $contactData) {
            // If contact has an ID, update it; otherwise create new
            if (!empty($contactData['id'])) {
                $contact = $employee->contacts()->find($contactData['id']);
                if ($contact) {
                    $contact->update(Arr::except($contactData, ['id', 'employee_id']));
                    $contactIds[] = $contact->id;
                }
            } else {
                $contact = $employee->contacts()->create(
                    Arr::except($contactData, ['id', 'employee_id'])
                );
                $contactIds[] = $contact->id;
            }
        }

        // Delete contacts that are not in the submitted list
        $employee->contacts()->whereNotIn('id', $contactIds)->delete();
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
