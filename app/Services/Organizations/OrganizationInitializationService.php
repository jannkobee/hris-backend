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
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class OrganizationInitializationService
{
    public function __construct(private TenantContext $context, private OrganizationDefaultsSeeder $defaults)
    {
    }

    public function initialize(Organization $organization): void
    {
        DB::transaction(function () use ($organization): void {
            // The catalogue is global. Do not delete extension permissions during setup.
            foreach (config('permissions.catalog', []) as $group => $permissions) {
                foreach ($permissions as $slug => [$name, $description]) {
                    Permission::updateOrCreate(['slug' => $slug], compact('name', 'description') + ['model' => $group]);
                }
            }

            $this->context->run($organization, function (): void {
                $admin = Role::firstOrCreate(['name' => 'Admin'], ['description' => 'Full organization access']);
                $admin->permissions()->syncWithoutDetaching(Permission::query()->pluck('id'));
                foreach (config('permissions.default_roles', []) as $name => $slugs) {
                    $role = Role::firstOrCreate(['name' => $name], ['description' => 'Standard organization role']);
                    // Complete empty roles left by baseline seeding; preserve customized assignments.
                    if ($role->wasRecentlyCreated || ! $role->permissions()->exists()) {
                        $role->permissions()->sync(Permission::query()->whereIn('slug', $slugs)->pluck('id'));
                    }
                }
            });
            $this->defaults->seed($organization);
        });
    }

    public function createAdministrator(Organization $organization, array $attributes): User
    {
        $attributes['admin_email'] = strtolower(trim((string) ($attributes['admin_email'] ?? '')));
        Validator::make($attributes, [
            'admin_email' => ['required', 'email', 'max:255'],
            'admin_password' => ['required', Password::min(12)->mixedCase()->numbers()->symbols()],
            'admin_first_name' => ['nullable', 'string', 'max:255'],
            'admin_last_name' => ['nullable', 'string', 'max:255'],
        ])->validate();

        return $this->context->run($organization, function () use ($attributes): User {
            if (User::query()->where('email', $attributes['admin_email'])->exists()) {
                throw ValidationException::withMessages(['admin_email' => 'An account with this email already exists in this workspace. Its password and role were not changed.']);
            }
            $role = Role::query()->where('name', 'Admin')->firstOrFail();

            return User::create([
                'role_id' => $role->id,
                'first_name' => $attributes['admin_first_name'] ?? 'Administrator',
                'last_name' => $attributes['admin_last_name'] ?? null,
                'email' => $attributes['admin_email'],
                'birthday' => now()->toDateString(),
                'is_active' => true,
                'password' => Hash::make($attributes['admin_password']),
            ]);
        });
    }
}
