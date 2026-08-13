<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Logs\AuditLog;
use App\Models\Overtime;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionAndAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_requires_the_same_curated_permissions_exposed_to_roles(): void
    {
        [$user, $role] = $this->userWithRole('HR Officer');

        $this->actingAs($user, 'sanctum')
            ->getJson(route('users.index'))
            ->assertForbidden();

        $viewUsers = $this->permission('User Management', 'view-users');
        $role->permissions()->attach($viewUsers);
        $user->unsetRelation('role');

        $this->actingAs($user, 'sanctum')
            ->getJson(route('users.index'))
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->postJson(route('departments.store'), ['name' => 'Operations'])
            ->assertForbidden();

        $role->permissions()->attach(
            $this->permission('Organization Setup', 'manage-departments')
        );
        $user->unsetRelation('role');

        $this->actingAs($user, 'sanctum')
            ->postJson(route('departments.store'), ['name' => 'Operations'])
            ->assertCreated();
    }

    public function test_employee_overtime_is_scoped_and_cannot_be_submitted_for_someone_else(): void
    {
        [$user, $role] = $this->userWithRole('User');
        $role->permissions()->attach([
            $this->permission('Overtime', 'view-overtimes')->id,
            $this->permission('Overtime', 'create-overtimes')->id,
        ]);

        $employee = $this->employeeFor($user, 'EMP-001');
        [$otherUser] = $this->userWithRole('Other User');
        $otherEmployee = $this->employeeFor($otherUser, 'EMP-002');

        Overtime::create([
            'employee_id' => $otherEmployee->id,
            'date' => '2026-08-12',
            'time_start' => '18:00',
            'time_end' => '20:00',
            'hours' => 2,
            'reason' => 'Month-end work',
        ]);

        $payload = [
            'employee_id' => $otherEmployee->id,
            'date' => '2026-08-13',
            'time_start' => '18:00',
            'time_end' => '20:00',
            'hours' => 2,
            'reason' => 'Support',
        ];

        $this->actingAs($user, 'sanctum')
            ->postJson(route('overtime.store'), $payload)
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->postJson(route('overtime.store'), [
                ...$payload,
                'employee_id' => $employee->id,
            ])
            ->assertCreated();

        $this->actingAs($user, 'sanctum')
            ->getJson(route('overtime.index'))
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.employee_id', $employee->id);
    }

    public function test_audit_log_uses_real_fields_context_and_redacts_sensitive_values(): void
    {
        [$user, $role] = $this->userWithRole('Auditor');
        $role->permissions()->attach([
            $this->permission('Organization Setup', 'manage-departments')->id,
            $this->permission('Audit and Settings', 'view-audit-logs')->id,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson(route('departments.store'), [
                'name' => 'Finance',
                'description' => 'Financial operations',
            ])
            ->assertCreated();

        $log = AuditLog::query()->latest()->firstOrFail();
        $this->assertSame(Department::class, $log->module);
        $this->assertSame('POST', $log->http_method);
        $this->assertSame('departments.store', $log->route_name);
        $this->assertSame('Finance', $log->payload['after']['name']);

        app(\App\Services\AuditLog\AuditLogServiceInterface::class)->insertLog(
            new User(),
            'credentials test',
            ['password' => 'plain-text', 'nested' => ['access_token' => 'token-value']]
        );

        $sensitiveLog = AuditLog::query()
            ->where('action', 'credentials test')
            ->firstOrFail();
        $this->assertSame('[REDACTED]', $sensitiveLog->payload['password']);
        $this->assertSame('[REDACTED]', $sensitiveLog->payload['nested']['access_token']);

        $this->actingAs($user, 'sanctum')
            ->getJson(route('audit-logs.index'))
            ->assertOk()
            ->assertJsonFragment([
                'module' => User::class,
                'action' => 'credentials test',
            ]);
    }

    private function userWithRole(string $roleName): array
    {
        $role = Role::create(['name' => $roleName]);
        $user = User::factory()->create(['role_id' => $role->id]);

        return [$user, $role];
    }

    private function permission(string $group, string $slug): Permission
    {
        return Permission::firstOrCreate(
            ['slug' => $slug],
            [
                'model' => $group,
                'name' => $slug,
                'description' => "Test permission for {$slug}",
            ]
        );
    }

    private function employeeFor(User $user, string $employeeNo): Employee
    {
        return Employee::create([
            'user_id' => $user->id,
            'employee_no' => $employeeNo,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'hire_date' => '2025-01-01',
        ]);
    }
}
