<?php

namespace App\Services\Organizations;

use App\Models\Organization;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class OrganizationProvisioningService
{
    public function __construct(
        private SubscriptionLifecycleService $subscriptions,
        private OrganizationInitializationService $initialization,
    ) {
    }

    public function provision(array $attributes): Organization
    {
        $invite = filter_var($attributes['send_owner_invitation'] ?? false, FILTER_VALIDATE_BOOLEAN);
        Validator::make($attributes, [
            'slug' => ['required', 'regex:/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/'],
            'name' => ['required', 'string', 'max:255'],
            'timezone' => ['required', 'timezone'],
            'country_code' => ['required', 'string', 'size:2', 'alpha'],
            'plan_code' => ['required', Rule::in(array_keys(config('plans.plans', [])))],
            'admin_email' => ['required', 'email', 'max:255'],
            'admin_password' => [Rule::requiredIf(! $invite), Rule::prohibitedIf($invite), 'nullable', Password::min(12)->mixedCase()->numbers()->symbols()],
            'send_owner_invitation' => ['sometimes', 'boolean'],
        ])->validate();

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
                'subscription_status' => $attributes['subscription_status'] ?? ($planCode === 'basic_free' ? Organization::SUBSCRIPTION_ACTIVE : Organization::SUBSCRIPTION_TRIALING),
                'trial_ends_at' => ($attributes['subscription_status'] ?? ($planCode === 'basic_free' ? 'active' : 'trialing')) === 'trialing'
                    ? ($attributes['trial_ends_at'] ?? now()->addDays((int) config('platform.trial_days', 14))) : null,
                'current_period_ends_at' => $attributes['current_period_ends_at'] ?? null,
                'employee_limit' => $attributes['employee_limit'] ?? null,
            ]);

            $this->initialization->initialize($organization);
            if (filled($attributes['admin_password'] ?? null)) {
                $this->initialization->createAdministrator($organization, $attributes);
            }

            $organization = $organization->fresh();
            $this->subscriptions->recordProvisioned($organization);

            return $organization;
        });
    }

    public function generateAvailableSlug(string $organizationName): string
    {
        $base = Str::slug($organizationName);
        $base = $base !== '' ? $base : 'workspace';
        $base = Str::limit($base, 63, '');
        $candidate = $base;
        $suffix = 2;

        while (Organization::query()->where('slug', $candidate)->exists()) {
            $suffixText = '-'.$suffix;
            $candidate = Str::limit($base, 63 - strlen($suffixText), '').$suffixText;
            $suffix++;
        }

        return $candidate;
    }

    public function updateSubscription(Organization $organization, array $attributes): Organization
    {
        return $this->subscriptions->update($organization, $attributes);
    }
}
