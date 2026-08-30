<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Services\Plans\PlanEntitlementService;
use Illuminate\Console\Command;

class SetOrganizationPlan extends Command
{
    protected $signature = 'organizations:set-plan
        {slug : Organization slug, such as legacy}
        {plan : Configured plan code, such as basic or enterprise}';

    protected $description = 'Change an organization subscription plan from the trusted server console.';

    public function handle(PlanEntitlementService $entitlements): int
    {
        $plan = strtolower(trim((string) $this->argument('plan')));

        if (! $entitlements->planExists($plan)) {
            $this->error("Unknown plan [{$plan}]. Available plans: ".implode(', ', array_keys(config('plans.plans', []))).'.');

            return self::FAILURE;
        }

        $organization = Organization::query()
            ->where('slug', strtolower(trim((string) $this->argument('slug'))))
            ->first();

        if (! $organization) {
            $this->error('Organization not found.');

            return self::FAILURE;
        }

        $organization->update(['plan_code' => $plan]);
        $this->info("{$organization->name} is now on the {$plan} plan.");

        return self::SUCCESS;
    }
}
