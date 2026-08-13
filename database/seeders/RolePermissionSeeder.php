<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Role::query()->where('name', 'Admin')->first();
        $admin?->permissions()->sync(Permission::query()->pluck('id'));

        foreach (config('permissions.default_roles', []) as $roleName => $slugs) {
            $role = Role::query()->where('name', $roleName)->first();
            $permissionIds = Permission::query()->whereIn('slug', $slugs)->pluck('id');
            $role?->permissions()->syncWithoutDetaching($permissionIds);
        }
    }
}
