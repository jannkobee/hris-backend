<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionPresetTest extends TestCase
{
    use RefreshDatabase;

    public function test_permission_presets_require_authentication_and_role_management_permission(): void
    {
        $this->getJson(route('permission-presets.index'))
            ->assertUnauthorized();

        [$user] = $this->userWithRole('Employee');

        $this->actingAs($user, 'sanctum')
            ->getJson(route('permission-presets.index'))
            ->assertForbidden();
    }

    public function test_permission_presets_resolve_existing_global_permissions_and_report_missing_slugs(): void
    {
        config()->set('permissions.role_templates', [
            'hr-manager' => [
                'name' => 'HR Manager',
                'description' => 'HR role template.',
                'icon' => 'mdi-account-tie-outline',
                'color' => 'secondary',
                'permission_slugs' => [
                    'view-users',
                    'permission-not-seeded',
                    'manage-users',
                    'view-users',
                ],
            ],
        ]);

        $viewUsers = $this->permission('User Management', 'view-users');
        $manageUsers = $this->permission('User Management', 'manage-users');
        $manageRolePermissions = $this->permission('Role Management', 'manage-role-permissions');

        [$user, $role] = $this->userWithRole('Administrator');
        $role->permissions()->attach($manageRolePermissions);

        $this->actingAs($user, 'sanctum')
            ->getJson(route('permission-presets.index'))
            ->assertOk()
            ->assertExactJson([
                'data' => [[
                    'key' => 'hr-manager',
                    'name' => 'HR Manager',
                    'description' => 'HR role template.',
                    'icon' => 'mdi-account-tie-outline',
                    'color' => 'secondary',
                    'permission_ids' => [$viewUsers->id, $manageUsers->id],
                    'permission_slugs' => ['view-users', 'manage-users'],
                    'missing_permission_slugs' => ['permission-not-seeded'],
                ]],
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
        return Permission::create([
            'model' => $group,
            'name' => $slug,
            'slug' => $slug,
            'description' => "Test permission for {$slug}",
        ]);
    }
}
