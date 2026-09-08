<?php

namespace App\Services\Plans;

use App\Models\Organization;

class PlanEntitlementService
{
    public function allows(?Organization $organization, string $feature): bool
    {
        if (! $organization || ! $organization->subscriptionAllowsAccess() || ! array_key_exists($feature, config('plans.features', []))) {
            return false;
        }

        $features = $this->configuredFeatures($organization->plan_code);

        if (! $this->availableInCountry($organization, $feature)) {
            return false;
        }

        return in_array('*', $features, true) || in_array($feature, $features, true);
    }

    public function payload(Organization $organization): array
    {
        $planCode = $this->normalizedPlanCode($organization->plan_code);
        $plan = config("plans.plans.{$planCode}", []);
        $featureDefinitions = config('plans.features', []);
        $configuredFeatures = $this->configuredFeatures($planCode);
        $enabledFeatures = in_array('*', $configuredFeatures, true)
            ? array_keys($featureDefinitions)
            : array_values(array_intersect($configuredFeatures, array_keys($featureDefinitions)));
        $enabledFeatures = array_values(array_filter($enabledFeatures,
            fn (string $feature): bool => $this->availableInCountry($organization, $feature)));

        return [
            'code' => $planCode,
            'name' => $plan['name'] ?? 'Unavailable plan',
            'description' => $plan['description'] ?? 'This organization does not have a valid subscription plan.',
            'features' => $enabledFeatures,
            'feature_details' => collect($enabledFeatures)
                ->mapWithKeys(fn (string $feature): array => [
                    $feature => $featureDefinitions[$feature],
                ])
                ->all(),
            'limits' => $this->limits($organization),
        ];
    }

    public function employeeLimit(Organization $organization): ?int
    {
        if ($organization->plan_code === 'basic_free') {
            return (int) app(PlatformPricingService::class)->current()['free_employee_limit'];
        }

        return $organization->employee_limit ?? $this->limits($organization)['employees'] ?? null;
    }

    private function limits(Organization $organization): array
    {
        if ($organization->plan_code === 'basic_free') {
            return ['employees' => $this->employeeLimit($organization)];
        }
        return config('plans.plans.'.$this->normalizedPlanCode($organization->plan_code).'.limits', []);
    }

    private function availableInCountry(Organization $organization, string $feature): bool
    {
        $countries = config("plans.features.{$feature}.countries");

        return $countries === null || in_array(strtoupper((string) $organization->country_code), $countries, true);
    }

    public function planExists(string $planCode): bool
    {
        return array_key_exists($this->normalizedPlanCode($planCode), config('plans.plans', []));
    }

    private function configuredFeatures(?string $planCode): array
    {
        $normalizedPlanCode = $this->normalizedPlanCode($planCode);

        if (! $this->planExists($normalizedPlanCode)) {
            return [];
        }

        return config("plans.plans.{$normalizedPlanCode}.features", []);
    }

    private function normalizedPlanCode(?string $planCode): string
    {
        return strtolower(trim((string) $planCode));
    }
}
