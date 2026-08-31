<?php

namespace App\Services\Organizations;

use App\Models\Organization;
use App\Models\SubscriptionEvent;
use App\Tenancy\TenantContext;

class SubscriptionLifecycleService
{
    private TenantContext $tenantContext;

    public function __construct(TenantContext $tenantContext)
    {
        $this->tenantContext = $tenantContext;
    }

    public function recordProvisioned(Organization $organization): void
    {
        $this->record($organization, 'organization_provisioned', null, $organization->plan_code, null, $organization->subscription_status, 'platform');
    }

    public function update(Organization $organization, array $attributes, string $source = 'platform', string $reference = null): Organization
    {
        $beforePlan = $organization->plan_code;
        $beforeStatus = $organization->subscription_status;
        $organization->update([
            'plan_code' => $attributes['plan_code'],
            'subscription_status' => $attributes['subscription_status'],
            'trial_ends_at' => $attributes['trial_ends_at'] ?? null,
            'current_period_ends_at' => $attributes['current_period_ends_at'] ?? null,
            'employee_limit' => $attributes['employee_limit'] ?? null,
        ]);
        $organization->refresh();

        $this->record(
            $organization,
            $beforePlan !== $organization->plan_code ? 'plan_changed' : 'subscription_updated',
            $beforePlan,
            $organization->plan_code,
            $beforeStatus,
            $organization->subscription_status,
            $source,
            $reference,
            ['trial_ends_at' => $organization->trial_ends_at?->toIso8601String(), 'current_period_ends_at' => $organization->current_period_ends_at?->toIso8601String()]
        );

        return $organization;
    }

    public function reconcile(Organization $organization): ?Organization
    {
        $nextStatus = $this->statusFor($organization);
        if ($nextStatus === null || $nextStatus === $organization->subscription_status) {
            return null;
        }

        return $this->update($organization, [
            'plan_code' => $organization->plan_code,
            'subscription_status' => $nextStatus,
            'trial_ends_at' => $organization->trial_ends_at,
            'current_period_ends_at' => $organization->current_period_ends_at,
            'employee_limit' => $organization->employee_limit,
        ], 'lifecycle');
    }

    private function statusFor(Organization $organization): ?string
    {
        if ($organization->subscription_status === Organization::SUBSCRIPTION_TRIALING
            && $organization->trial_ends_at?->isPast()) {
            return Organization::SUBSCRIPTION_SUSPENDED;
        }

        if (in_array($organization->subscription_status, [Organization::SUBSCRIPTION_ACTIVE, Organization::SUBSCRIPTION_PAST_DUE], true)
            && $organization->current_period_ends_at?->isPast()) {
            $graceEndsAt = $organization->current_period_ends_at->copy()->addDays((int) config('billing.past_due_grace_days'));

            return $graceEndsAt->isPast()
                ? Organization::SUBSCRIPTION_SUSPENDED
                : Organization::SUBSCRIPTION_PAST_DUE;
        }

        return null;
    }

    private function record(Organization $organization, string $eventType, ?string $fromPlan, ?string $toPlan, ?string $fromStatus, ?string $toStatus, string $source, string $reference = null, array $metadata = []): void
    {
        $this->tenantContext->run($organization, function () use ($eventType, $fromPlan, $toPlan, $fromStatus, $toStatus, $source, $reference, $metadata): void {
            SubscriptionEvent::create([
                'event_type' => $eventType,
                'from_plan_code' => $fromPlan,
                'to_plan_code' => $toPlan,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'source' => $source,
                'reference' => $reference,
                'metadata' => $metadata,
            ]);
        });
    }
}
