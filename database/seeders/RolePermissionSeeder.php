<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Tenancy\TenantContext;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $context = app(TenantContext::class);

        Organization::query()->get()->each(function (Organization $organization) use ($context): void {
            $context->run($organization, function (): void {
                $admin = Role::query()->where('name', 'Admin')->first();
                $admin?->permissions()->sync(Permission::query()->pluck('id'));

                foreach (config('permissions.default_roles', []) as $roleName => $slugs) {
                    $role = Role::query()->where('name', $roleName)->first();
                    $permissionIds = Permission::query()->whereIn('slug', $slugs)->pluck('id');
                    $role?->permissions()->syncWithoutDetaching($permissionIds);
                }
            });
        });
    }
}
