<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Role;
use App\Tenancy\TenantContext;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Admin',
                'description' => 'admin',
            ],
            [
                'name' => 'User',
                'description' => 'user',
            ],
        ];

        $context = app(TenantContext::class);

        Organization::query()->get()->each(function (Organization $organization) use ($context, $roles): void {
            $context->run($organization, function () use ($roles): void {
                foreach ($roles as $role) {
                    Role::firstOrCreate(['name' => $role['name']], $role);
                }
            });
        });
    }
}
