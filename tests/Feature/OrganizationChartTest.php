<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationChartTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_retrieve_hierarchical_org_chart(): void
    {
        $organization = app(TenantContext::class)->organization();
        $role = Role::firstOrCreate(['name' => 'Admin']);
        $adminUser = User::factory()->create(['role_id' => $role->id]);

        $dept = Department::create(['name' => 'Engineering']);
        $posLeader = Position::create(['name' => 'Tech Lead', 'department_id' => $dept->id]);
        $posDev = Position::create(['name' => 'Software Engineer', 'department_id' => $dept->id]);

        $userLeader = User::factory()->create(['role_id' => $role->id, 'first_name' => 'Lead', 'last_name' => 'Dev']);
        $leader = Employee::create([
            'user_id' => $userLeader->id,
            'employee_no' => 'EMP-LEAD-001',
            'department_id' => $dept->id,
            'position_id' => $posLeader->id,
            'hire_date' => '2024-01-01',
        ]);

        $userDev = User::factory()->create(['role_id' => $role->id, 'first_name' => 'Junior', 'last_name' => 'Dev']);
        $dev = Employee::create([
            'user_id' => $userDev->id,
            'manager_id' => $leader->id,
            'employee_no' => 'EMP-DEV-001',
            'department_id' => $dept->id,
            'position_id' => $posDev->id,
            'hire_date' => '2024-02-01',
        ]);

        $this->actingAs($adminUser, 'sanctum');

        $response = $this->getJson('/backend/api/v1/organization-chart');

        $response->assertOk();
        $response->assertJsonStructure([
            'message',
            'data' => [
                'total_employees',
                'tree' => [
                    '*' => [
                        'id',
                        'employee_no',
                        'user' => ['id', 'name', 'email'],
                        'department' => ['id', 'name'],
                        'position' => ['id', 'name'],
                        'direct_reports_count',
                        'children',
                    ],
                ],
            ],
        ]);

        $data = $response->json('data');
        $this->assertGreaterThanOrEqual(2, $data['total_employees']);

        $tree = collect($data['tree']);
        $leaderNode = $tree->firstWhere('id', $leader->id);
        $this->assertNotNull($leaderNode);
        $this->assertSame(1, $leaderNode['direct_reports_count']);
        $this->assertSame($dev->id, $leaderNode['children'][0]['id']);
    }

    public function test_org_chart_is_isolated_by_tenant(): void
    {
        $orgA = app(TenantContext::class)->organization();
        $roleA = Role::firstOrCreate(['name' => 'Admin']);
        $userA = User::factory()->create(['role_id' => $roleA->id]);
        $empA = Employee::create([
            'user_id' => $userA->id,
            'employee_no' => 'EMP-A',
            'hire_date' => '2024-01-01',
        ]);

        // Create Org B
        $orgB = Organization::create([
            'slug' => 'tenant-b-chart',
            'name' => 'Tenant B',
            'timezone' => 'Asia/Manila',
            'country_code' => 'PH',
            'plan_code' => Organization::PLAN_ENTERPRISE,
            'status' => Organization::STATUS_ACTIVE,
        ]);

        app(TenantContext::class)->run($orgB, function () {
            $roleB = Role::create(['name' => 'Admin']);
            $userB = User::factory()->create(['role_id' => $roleB->id]);
            Employee::create([
                'user_id' => $userB->id,
                'employee_no' => 'EMP-B',
                'hire_date' => '2024-01-01',
            ]);
        });

        // Request as Org A
        $this->actingAs($userA, 'sanctum');
        $response = $this->getJson('/backend/api/v1/organization-chart');

        $response->assertOk();
        $treeJson = json_encode($response->json('data.tree'));
        $this->assertStringContainsString('EMP-A', $treeJson);
        $this->assertStringNotContainsString('EMP-B', $treeJson);
    }
}

