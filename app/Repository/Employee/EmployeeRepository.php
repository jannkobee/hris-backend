<?php

namespace App\Repository\Employee;

use App\Models\Employee;
use App\Repository\Base\BaseRepository;
use App\Services\AuditLog\AuditLogServiceInterface;
use App\Services\EmployeeNumber\EmployeeNumberServiceInterface;
use App\Services\Integrations\WebhookDispatcher;
use App\Services\LeaveAccrual\LeaveCreditAccrualService;
use App\Services\Plans\PlanEntitlementService;
use App\Services\Utils\ResponseServiceInterface;
use App\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class EmployeeRepository extends BaseRepository implements EmployeeRepositoryInterface
{
    protected EmployeeNumberServiceInterface $employeeNumberService;

    private LeaveCreditAccrualService $leaveCreditAccrualService;

    private PlanEntitlementService $planEntitlements;

    private TenantContext $tenantContext;

    private WebhookDispatcher $webhooks;

    public function __construct(
        Employee $model,
        ResponseServiceInterface $responseService,
        AuditLogServiceInterface $auditLogService,
        EmployeeNumberServiceInterface $employeeNumberService,
        LeaveCreditAccrualService $leaveCreditAccrualService,
        PlanEntitlementService $planEntitlements,
        TenantContext $tenantContext,
        WebhookDispatcher $webhooks,
    ) {
        parent::__construct($model, $responseService, $auditLogService);
        $this->employeeNumberService = $employeeNumberService;
        $this->leaveCreditAccrualService = $leaveCreditAccrualService;
        $this->planEntitlements = $planEntitlements;
        $this->tenantContext = $tenantContext;
        $this->webhooks = $webhooks;
    }

    public function create(array $attributes): JsonResponse
    {
        $this->ensureEmployeeCapacity();

        $attributes['user_id'] = $attributes['user_id'] ?? data_get($attributes, 'user.id');
        $attributes['employment_status_id'] = $attributes['employment_status_id'] ?? data_get($attributes, 'employmentStatus.id');
        $attributes['department_id'] = $attributes['department_id'] ?? data_get($attributes, 'department.id');
        $attributes['position_id'] = $attributes['position_id'] ?? data_get($attributes, 'position.id');
        $attributes['job_grade_id'] = $attributes['job_grade_id'] ?? data_get($attributes, 'jobGrade.id');

        $attributes['employee_no'] = $attributes['employee_no'] ?? $this->employeeNumberService->generate();

        $addresses = $attributes['addresses'] ?? [];
        $contacts = $attributes['contacts'] ?? [];

        $attributes = Arr::except($attributes, ['user', 'employmentStatus', 'department', 'position', 'jobGrade', 'addresses', 'contacts']);
        $attributes = Arr::only($attributes, $this->model->getFillable());

        $response = parent::create($attributes);
        $responseData = json_decode($response->getContent(), true);

        if ($response->isSuccessful() && isset($responseData['data']['id'])) {
            $employeeId = $responseData['data']['id'];
            $employee = Employee::find($employeeId);

            $this->ensureNotOwnManager($employee);

            if (! empty($addresses)) {
                $this->syncAddresses($employee, $addresses);
            }
            if (! empty($contacts)) {
                $this->syncContacts($employee, $contacts);
            }

            $accrual = $this->leaveCreditAccrualService->accrueEmployee($employee);
            $employee->setAttribute('leave_credits_accrued', $accrual['credited']);

            $employee->load(['addresses', 'contacts', 'leaveCredits.leaveType']);
            $this->webhooks->dispatch('employee.created', $this->webhookPayload($employee));

            return $this->responseService->resolveResponse('Employee created successfully', $employee);
        }

        return $response;
    }

    private function ensureEmployeeCapacity(): void
    {
        $organization = $this->tenantContext->organization();
        $limit = $this->planEntitlements->employeeLimit($organization);

        if ($limit !== null && Employee::query()->count() >= $limit) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'organization' => "Your {$organization->plan_code} plan is limited to {$limit} employees. Upgrade your plan or increase its employee limit to add another employee.",
            ]);
        }
    }

    public function update(array $attributes, $id): JsonResponse
    {
        $attributes['user_id'] = $attributes['user_id'] ?? data_get($attributes, 'user.id');
        $attributes['employment_status_id'] = $attributes['employment_status_id'] ?? data_get($attributes, 'employmentStatus.id');
        $attributes['department_id'] = $attributes['department_id'] ?? data_get($attributes, 'department.id');
        $attributes['position_id'] = $attributes['position_id'] ?? data_get($attributes, 'position.id');
        $attributes['job_grade_id'] = $attributes['job_grade_id'] ?? data_get($attributes, 'jobGrade.id');

        if (! array_key_exists('employee_no', $attributes) || blank($attributes['employee_no'])) {
            unset($attributes['employee_no']);
        }

        $addresses = $attributes['addresses'] ?? null;
        $contacts = $attributes['contacts'] ?? null;

        $attributes = Arr::except($attributes, ['user', 'employmentStatus', 'department', 'position', 'jobGrade', 'addresses', 'contacts']);
        $attributes = Arr::only($attributes, $this->model->getFillable());

        $response = parent::update($attributes, $id);

        if ($response->isSuccessful()) {
            $employee = Employee::find($id);

            $this->ensureNotOwnManager($employee);

            if ($addresses !== null) {
                $this->syncAddresses($employee, $addresses);
            }
            if ($contacts !== null) {
                $this->syncContacts($employee, $contacts);
            }

            $employee->load(['addresses', 'contacts']);
            $this->webhooks->dispatch('employee.updated', $this->webhookPayload($employee));

            return $this->responseService->resolveResponse('Employee updated successfully', $employee);
        }

        return $response;
    }

    private function webhookPayload(Employee $employee): array
    {
        return [
            'id' => $employee->id,
            'employee_no' => $employee->employee_no,
            'user_id' => $employee->user_id,
            'department_id' => $employee->department_id,
            'position_id' => $employee->position_id,
            'employment_status_id' => $employee->employment_status_id,
            'updated_at' => $employee->updated_at?->toIso8601String(),
        ];
    }

    private function ensureNotOwnManager(Employee $employee): void
    {
        if ($employee->manager_id !== null && $employee->manager_id === $employee->getKey()) {
            throw \Illuminate\Validation\ValidationException::withMessages(['manager_id' => 'An employee cannot report to themselves.']);
        }
    }

    public function generateEmployeeNo(): JsonResponse
    {
        return $this->responseService->resolveResponse(
            'Employee Number generated successfully',
            ['employee_no' => $this->employeeNumberService->generate()]
        );
    }

    public function updateEmployeeNumberSettings(array $data): JsonResponse
    {
        $settings = $this->employeeNumberService->updateSettings($data);

        return $this->responseService->resolveResponse(
            'Employee number settings updated successfully',
            $settings
        );
    }

    public function getEmployeeNumberSettings(): JsonResponse
    {
        return $this->responseService->resolveResponse(
            'Employee number settings retrieved successfully',
            $this->employeeNumberService->getSettings()
        );
    }

    private function syncAddresses(Employee $employee, array $addresses): void
    {
        $addressIds = [];

        foreach ($addresses as $addressData) {
            if (! empty($addressData['id'])) {
                $address = $employee->addresses()->find($addressData['id']);
                if ($address) {
                    $address->update(Arr::except($addressData, ['id', 'employee_id']));
                    $addressIds[] = $address->id;
                }
            } else {
                $address = $employee->addresses()->create(Arr::except($addressData, ['id', 'employee_id']));
                $addressIds[] = $address->id;
            }
        }

        $employee->addresses()->whereNotIn('id', $addressIds)->delete();
    }

    private function syncContacts(Employee $employee, array $contacts): void
    {
        $contactIds = [];

        foreach ($contacts as $contactData) {
            if (! empty($contactData['id'])) {
                $contact = $employee->contacts()->find($contactData['id']);
                if ($contact) {
                    $contact->update(Arr::except($contactData, ['id', 'employee_id']));
                    $contactIds[] = $contact->id;
                }
            } else {
                $contact = $employee->contacts()->create(Arr::except($contactData, ['id', 'employee_id']));
                $contactIds[] = $contact->id;
            }
        }

        $employee->contacts()->whereNotIn('id', $contactIds)->delete();
    }

    public function reformatEmployeeNumbers(): JsonResponse
    {
        DB::beginTransaction();

        try {
            $employees = Employee::orderBy('created_at', 'asc')->get();
            $count = 0;

            foreach ($employees as $employee) {
                $newNumber = $this->employeeNumberService->generate();

                $employee->employee_no = $newNumber;
                $employee->saveQuietly();

                $count++;
            }

            DB::commit();

            return $this->responseService->resolveResponse(
                "Successfully reformatted {$count} employee numbers.",
                ['count' => $count]
            );
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to reformat employee numbers.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
