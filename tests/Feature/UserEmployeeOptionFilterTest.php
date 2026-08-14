<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserEmployeeOptionFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_user_options_exclude_accounts_already_linked_to_an_employee(): void
    {
        $role = Role::create(['name' => 'HR user']);
        $permission = Permission::create([
            'slug' => 'view-users',
            'model' => 'Users',
            'name' => 'View users',
            'description' => 'View user accounts',
        ]);
        $role->permissions()->attach($permission->id);

        $viewer = User::factory()->create(['role_id' => $role->id]);
        $availableUser = User::factory()->create();
        $linkedUser = User::factory()->create();
        Employee::create([
            'user_id' => $linkedUser->id,
            'employee_no' => 'EMP-LINKED-USER',
        ]);

        $response = $this->actingAs($viewer, 'sanctum')->getJson(route('users.index', [
            'all' => 1,
            'without_employee' => 1,
            'require_email' => 1,
        ]))->assertOk();

        $userIds = collect($response->json('data'))->pluck('id');
        $this->assertTrue($userIds->contains($availableUser->id));
        $this->assertFalse($userIds->contains($linkedUser->id));
    }
}
