<?php

namespace App\Services\Organizations;

use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Tenancy\TenantContext;
use Database\Seeders\OrganizationDefaultsSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrganizationProvisioningService
{
    private TenantContext $tenantContext;

    private OrganizationDefaultsSeeder $defaults;

    private SubscriptionLifecycleService $subscriptions;

    public function __construct(
        TenantContext $tenantContext,
        OrganizationDefaultsSeeder $defaults,
        SubscriptionLifecycleService $subscriptions,
    ) {
        $this->tenantContext = $tenantContext;
        $this->defaults = $defaults;
        $this->subscriptions = $subscriptions;
    }

    public function provision(array $attributes): Organization
    {
        $slug = Str::lower(trim((string) $attributes['slug']));
        $planCode = Str::lower(trim((string) $attributes['plan_code']));

        if (Organization::query()->where('slug', $slug)->exists()) {
            throw ValidationException::withMessages(['slug' => 'This organization slug is already in use.']);
        }

        return DB::transaction(function () use ($attributes, $slug, $planCode): Organization {
            $organization = Organization::create([
                'slug' => $slug,
                'name' => trim((string) $attributes['name']),
                'timezone' => $attributes['timezone'],
                'country_code' => Str::upper($attributes['country_code']),
                'plan_code' => $planCode,
                'status' => Organization::STATUS_ACTIVE,
                'subscription_status' => $attributes['subscription_status'] ?? Organization::SUBSCRIPTION_TRIALING,
                'trial_ends_at' => $attributes['trial_ends_at'] ?? now()->addDays(14),
                'current_period_ends_at' => $attributes['current_period_ends_at'] ?? null,
                'employee_limit' => $attributes['employee_limit'] ?? null,
            ]);

            $this->tenantContext->run($organization, function () use ($attributes): void {
                $admin = Role::firstOrCreate(
                    ['name' => 'Admin'],
                    ['description' => 'Full organization access']
                );
                Role::firstOrCreate(
                    ['name' => 'User'],
                    ['description' => 'Standard employee access']
                );
                $admin->permissions()->sync(Permission::query()->pluck('id'));

                if (filled($attributes['admin_email'] ?? null)) {
                    User::create([
                        'role_id' => $admin->id,
                        'first_name' => $attributes['admin_first_name'] ?? 'Administrator',
                        'last_name' => $attributes['admin_last_name'] ?? null,
                        'email' => Str::lower(trim((string) $attributes['admin_email'])),
                        'birthday' => now()->toDateString(),
                        'password' => Hash::make((string) $attributes['admin_password']),
                    ]);
                }
            });

            $this->defaults->seed($organization);

            $organization = $organization->fresh();
            $this->subscriptions->recordProvisioned($organization);

            return $organization;
        });
    }

    public function updateSubscription(Organization $organization, array $attributes): Organization
    {
        return $this->subscriptions->update($organization, $attributes);
    }
}
