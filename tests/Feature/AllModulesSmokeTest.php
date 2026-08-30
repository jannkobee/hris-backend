<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AllModulesSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_authenticated_module_read_entry_point_responds_successfully(): void
    {
        $organization = app(\App\Tenancy\TenantContext::class)->organization();
        $organization->update(['plan_code' => Organization::PLAN_ENTERPRISE]);

        $role = Role::firstOrCreate(['name' => 'Admin']);
        $admin = User::factory()->create(['role_id' => $role->id]);
        $this->actingAs($admin, 'sanctum');

        $endpoints = [
            'dashboard' => '/backend/api/v1/dashboard/overview?from=2026-08-01&to=2026-08-31',
            'profile' => '/backend/api/v1/profile',
            'users' => '/backend/api/v1/users',
            'roles' => '/backend/api/v1/roles',
            'permissions' => '/backend/api/v1/permissions',
            'permission presets' => '/backend/api/v1/permission-presets',
            'employees' => '/backend/api/v1/employees',
            'employee document categories' => '/backend/api/v1/employee-documents/categories',
            'attendance' => '/backend/api/v1/attendances',
            'holidays' => '/backend/api/v1/holidays',
            'departments' => '/backend/api/v1/departments',
            'positions' => '/backend/api/v1/positions',
            'employment statuses' => '/backend/api/v1/employment-statuses',
            'job grades' => '/backend/api/v1/job-grades',
            'leave types' => '/backend/api/v1/leave-types',
            'leave requests' => '/backend/api/v1/leave-requests',
            'leave credits' => '/backend/api/v1/leave-credits',
            'leave credit settings' => '/backend/api/v1/leave-credit-settings',
            'leave conversions' => '/backend/api/v1/leave-conversion-requests',
            'overtime' => '/backend/api/v1/overtime',
            'announcements' => '/backend/api/v1/announcements',
            'conversations' => '/backend/api/v1/conversations',
            'notes' => '/backend/api/v1/notes',
            'payroll' => '/backend/api/v1/payroll-periods',
            'workplace rooms' => '/backend/api/v1/workplace-hub/rooms',
            'workplace meetings' => '/backend/api/v1/workplace-hub/meetings',
            'scheduled tasks' => '/backend/api/v1/scheduled-tasks',
            'audit logs' => '/backend/api/v1/audit-logs',
            'app settings' => '/backend/api/v1/app-settings',
            'navigation badges' => '/backend/api/v1/navigation/badges',
            'realtime config' => '/backend/api/v1/realtime/config',
        ];

        $failures = [];
        foreach ($endpoints as $module => $endpoint) {
            $response = $this->getJson($endpoint);
            if (! $response->isSuccessful()) {
                $failures[$module] = [
                    'status' => $response->status(),
                    'response' => $response->json(),
                ];
            }
        }

        $this->assertSame([], $failures, 'One or more module entry points failed: '.json_encode($failures));
    }
}
