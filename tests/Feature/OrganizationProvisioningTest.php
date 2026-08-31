<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Role;
use App\Models\SubscriptionEvent;
use App\Models\User;
use App\Services\Plans\PlanEntitlementService;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationProvisioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_can_provision_an_isolated_trial_organization_with_an_administrator(): void
    {
        config()->set('platform.provisioning_key', 'platform-test-key');

        $response = $this->withHeader('X-Platform-Provisioning-Key', 'platform-test-key')
            ->postJson(route('platform.organizations.store'), $this->payload());

        $response->assertCreated()
            ->assertJsonPath('data.slug', 'acme')
            ->assertJsonPath('data.subscription_status', Organization::SUBSCRIPTION_TRIALING)
            ->assertJsonPath('data.plan.code', Organization::PLAN_BASIC);

        $organization = Organization::query()->where('slug', 'acme')->firstOrFail();
        $this->assertNotNull($organization->trial_ends_at);

        $this->withHeader('X-Platform-Provisioning-Key', 'platform-test-key')
            ->getJson(route('platform.organizations.show', $organization))
            ->assertOk()
            ->assertJsonPath('data.webhooks', []);

        app(TenantContext::class)->run($organization, function (): void {
            $adminRole = Role::query()->where('name', 'Admin')->firstOrFail();
            $admin = User::query()->where('email', 'admin@acme.test')->firstOrFail();

            $this->assertSame($adminRole->id, $admin->role_id);
            $this->assertNotEmpty($adminRole->permissions);
        });
    }

    public function test_platform_credentials_are_required_and_subscription_updates_change_entitlements(): void
    {
        config()->set('platform.provisioning_key', 'platform-test-key');

        $this->postJson(route('platform.organizations.store'), $this->payload())
            ->assertUnauthorized();

        $organization = Organization::create([
            'slug' => 'existing',
            'name' => 'Existing Organization',
            'timezone' => 'Asia/Manila',
            'plan_code' => Organization::PLAN_BASIC,
            'status' => Organization::STATUS_ACTIVE,
            'subscription_status' => Organization::SUBSCRIPTION_TRIALING,
            'trial_ends_at' => now()->addDay(),
        ]);

        $this->withHeader('X-Platform-Provisioning-Key', 'platform-test-key')
            ->patchJson(route('platform.organizations.subscription.update', $organization), [
                'plan_code' => Organization::PLAN_ENTERPRISE,
                'subscription_status' => Organization::SUBSCRIPTION_ACTIVE,
                'trial_ends_at' => null,
                'current_period_ends_at' => now()->addMonth()->toDateTimeString(),
                'employee_limit' => 200,
            ])
            ->assertAccepted()
            ->assertJsonPath('data.plan.code', Organization::PLAN_ENTERPRISE)
            ->assertJsonPath('data.employee_limit', 200);

        $organization->refresh();
        $this->assertSame(200, app(PlanEntitlementService::class)->employeeLimit($organization));

        app(TenantContext::class)->run($organization, function (): void {
            $event = SubscriptionEvent::query()->latest()->firstOrFail();

            $this->assertSame('plan_changed', $event->event_type);
            $this->assertSame(Organization::PLAN_BASIC, $event->from_plan_code);
            $this->assertSame(Organization::PLAN_ENTERPRISE, $event->to_plan_code);
        });
    }

    private function payload(): array
    {
        return [
            'slug' => 'acme',
            'name' => 'Acme HR',
            'timezone' => 'Asia/Manila',
            'plan_code' => Organization::PLAN_BASIC,
            'admin_first_name' => 'Ada',
            'admin_last_name' => 'Admin',
            'admin_email' => 'admin@acme.test',
            'admin_password' => 'ProvisionedPassword!123',
            'admin_password_confirmation' => 'ProvisionedPassword!123',
        ];
    }
}
