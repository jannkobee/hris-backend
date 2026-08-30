<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Tenancy\TenantContext;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organization = Organization::query()
            ->where('slug', config('tenancy.default_slug'))
            ->firstOrFail();

        app(TenantContext::class)->run($organization, function (): void {
            $role = Role::where('name', 'Admin')->firstOrFail();

            User::firstOrCreate(
                ['email' => 'admin@base.com'],
                [
                    'role_id' => $role->id,
                    'first_name' => 'Admin',
                    'gender' => 'Male',
                    'birthday' => Carbon::now()->format('Y-m-d'),
                    'password' => Hash::make('secret'),
                ]
            );
        });
    }
}
