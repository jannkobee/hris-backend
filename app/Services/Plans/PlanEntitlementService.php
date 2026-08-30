<?php

namespace App\Services\Plans;

use App\Models\Organization;

class PlanEntitlementService
{
    public function allows(?Organization $organization, string $feature): bool
    {
        if (! $organization || ! array_key_exists($feature, config('plans.features', []))) {
            return false;
        }

        $features = $this->configuredFeatures($organization->plan_code);

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
        ];
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
