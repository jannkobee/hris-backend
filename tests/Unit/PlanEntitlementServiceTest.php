<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Services\Plans\PlanEntitlementService;
use Tests\TestCase;

class PlanEntitlementServiceTest extends TestCase
{
    private PlanEntitlementService $entitlements;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entitlements = app(PlanEntitlementService::class);
    }

    public function test_basic_plan_only_allows_its_configured_features(): void
    {
        $organization = new Organization(['plan_code' => 'basic']);

        $this->assertTrue($this->entitlements->allows($organization, 'leave'));
        $this->assertTrue($this->entitlements->allows($organization, 'overtime'));
        $this->assertFalse($this->entitlements->allows($organization, 'payroll'));
        $this->assertFalse($this->entitlements->allows($organization, 'employee_documents'));
    }

    public function test_enterprise_expands_to_every_known_feature_but_rejects_unknown_features(): void
    {
        $organization = new Organization(['plan_code' => 'enterprise']);

        foreach (array_keys(config('plans.features')) as $feature) {
            $this->assertTrue($this->entitlements->allows($organization, $feature));
        }

        $this->assertFalse($this->entitlements->allows($organization, 'misspelled_feature'));
    }

    public function test_missing_organization_and_invalid_plans_fail_closed(): void
    {
        $this->assertFalse($this->entitlements->allows(null, 'leave'));
        $this->assertFalse(
            $this->entitlements->allows(new Organization(['plan_code' => 'not-a-plan']), 'leave')
        );
        $this->assertFalse(
            $this->entitlements->allows(new Organization(['plan_code' => null]), 'leave')
        );
    }

    public function test_payload_contains_only_enabled_features_and_their_details(): void
    {
        $payload = $this->entitlements->payload(
            new Organization(['plan_code' => 'basic'])
        );

        $this->assertSame('basic', $payload['code']);
        $this->assertSame(config('plans.plans.basic.features'), $payload['features']);
        $this->assertSame($payload['features'], array_keys($payload['feature_details']));
        $this->assertArrayNotHasKey('payroll', $payload['feature_details']);
    }

    public function test_invalid_plan_payload_does_not_inherit_the_configured_default(): void
    {
        config()->set('plans.default', 'enterprise');

        $payload = $this->entitlements->payload(
            new Organization(['plan_code' => 'invalid'])
        );

        $this->assertSame('invalid', $payload['code']);
        $this->assertSame([], $payload['features']);
        $this->assertSame([], $payload['feature_details']);
    }
}
