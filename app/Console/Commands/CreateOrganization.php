<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Tenancy\TenantContext;
use Database\Seeders\OrganizationDefaultsSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateOrganization extends Command
{
    protected $signature = 'organizations:create
        {slug : Unique URL-safe organization slug, such as acme}
        {name : Organization display name}
        {--plan=basic : Subscription plan (basic or enterprise)}
        {--admin-email= : Initial administrator email}
        {--admin-password= : Initial administrator password}';

    protected $description = 'Provision an organization with its system roles and optional initial administrator';

    public function handle(TenantContext $context, OrganizationDefaultsSeeder $defaults): int
    {
        $slug = Str::lower(trim((string) $this->argument('slug')));
        $plan = Str::lower(trim((string) $this->option('plan')));
        $email = $this->option('admin-email');
        $password = $this->option('admin-password');

        if (! preg_match('/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/', $slug)) {
            $this->error('The slug must contain lowercase letters, numbers, and internal hyphens only.');

            return self::FAILURE;
        }

        if (! in_array($plan, [Organization::PLAN_BASIC, Organization::PLAN_ENTERPRISE], true)) {
            $this->error('Plan must be basic or enterprise.');

            return self::FAILURE;
        }

        if (($email === null) !== ($password === null)) {
            $this->error('Provide both --admin-email and --admin-password, or neither.');

            return self::FAILURE;
        }

        if (Organization::query()->where('slug', $slug)->exists()) {
            $this->error("Organization '{$slug}' already exists.");

            return self::FAILURE;
        }

        DB::transaction(function () use ($context, $defaults, $slug, $plan, $email, $password): void {
            $organization = Organization::create([
                'slug' => $slug,
                'name' => trim((string) $this->argument('name')),
                'timezone' => config('tenancy.default_timezone', config('app.timezone')),
                'plan_code' => $plan,
                'status' => Organization::STATUS_ACTIVE,
            ]);

            $context->run($organization, function () use ($email, $password): void {
                $admin = Role::firstOrCreate(
                    ['name' => 'Admin'],
                    ['description' => 'Full organization access']
                );
                Role::firstOrCreate(
                    ['name' => 'User'],
                    ['description' => 'Standard employee access']
                );
                $admin->permissions()->sync(Permission::query()->pluck('id'));

                if ($email !== null) {
                    User::create([
                        'role_id' => $admin->id,
                        'first_name' => 'Administrator',
                        'email' => $email,
                        // The legacy user schema requires a birthday even for
                        // service-provisioned accounts. The administrator can
                        // replace this placeholder from their profile later.
                        'birthday' => now()->toDateString(),
                        'password' => Hash::make((string) $password),
                    ]);
                }
            });

            $defaults->seed($organization);
        });

        $this->info("Organization '{$slug}' created on the {$plan} plan.");
        if ($email === null) {
            $this->warn('No administrator was created. Re-run with --admin-email and --admin-password to provision one.');
        }

        return self::SUCCESS;
    }
}
