<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Services\Plans\PlatformPricingService;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TenantBillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_admin_can_view_billing_summary(): void
    {
        $organization = app(TenantContext::class)->organization();
        $organization->update(['country_code' => 'PH', 'plan_code' => 'basic_free']);

        $role = Role::create(['name' => 'Admin']);
        $admin = User::factory()->create(['role_id' => $role->id]);

        app(PlatformPricingService::class)->update([
            'free_employee_limit' => 10,
            'growth_price_per_employee' => 1900,
            'currency' => 'php',
        ]);

        for ($i = 1; $i <= 5; $i++) {
            Employee::create(['employee_no' => "EMP-{$i}"]);
        }

        $this->actingAs($admin, 'sanctum')
            ->getJson(route('billing.summary'))
            ->assertOk()
            ->assertJsonPath('data.plan_code', 'basic_free')
            ->assertJsonPath('data.active_employee_count', 5)
            ->assertJsonPath('data.free_employee_limit', 10)
            ->assertJsonPath('data.billable_employee_count', 0)
            ->assertJsonPath('data.monthly_amount', 0);
    }

    public function test_tenant_admin_can_create_checkout_session_for_growth(): void
    {
        config(['billing.stripe.secret_key' => 'sk_test_123']);
        Http::fake([
            '*' => Http::response(['id' => 'cs_test_session', 'url' => 'https://checkout.stripe.test/pay']),
        ]);

        $organization = app(TenantContext::class)->organization();
        $organization->update(['country_code' => 'PH', 'plan_code' => 'basic_free']);

        $role = Role::create(['name' => 'Admin']);
        $admin = User::factory()->create(['role_id' => $role->id]);

        app(PlatformPricingService::class)->update([
            'free_employee_limit' => 10,
            'growth_price_per_employee' => 1900,
            'currency' => 'php',
        ]);

        for ($i = 1; $i <= 10; $i++) {
            Employee::create(['employee_no' => "EMP-{$i}"]);
        }

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson(route('billing.checkout-sessions.store'), [
                'plan_code' => 'growth',
                'billing_interval' => 'month',
                'success_url' => 'https://example.test/billing?success=1',
                'cancel_url' => 'https://example.test/billing?cancelled=1',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.id', 'cs_test_session')
            ->assertJsonPath('data.url', 'https://checkout.stripe.test/pay');

        Http::assertSent(function ($request) {
            return $request['line_items'][0]['quantity'] === 1
                && $request['line_items'][0]['price_data']['unit_amount'] === 1900
                && $request['line_items'][0]['price_data']['currency'] === 'php';
        });
    }

    public function test_non_admin_cannot_initiate_checkout(): void
    {
        $organization = app(TenantContext::class)->organization();
        $role = Role::create(['name' => 'Staff']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user, 'sanctum')
            ->postJson(route('billing.checkout-sessions.store'), [
                'plan_code' => 'growth',
                'billing_interval' => 'month',
                'success_url' => 'https://example.test/billing',
                'cancel_url' => 'https://example.test/billing',
            ])
            ->assertForbidden();
    }

    public function test_payment_failure_webhook_sets_subscription_to_past_due(): void
    {
        $organization = app(TenantContext::class)->organization();
        $organization->update([
            'billing_provider' => 'stripe',
            'billing_subscription_id' => 'sub_failing_123',
            'subscription_status' => Organization::SUBSCRIPTION_ACTIVE,
        ]);

        config(['billing.stripe.webhook_secret' => 'whsec_test']);
        $payload = json_encode([
            'type' => 'invoice.payment_failed',
            'data' => [
                'object' => [
                    'subscription' => 'sub_failing_123',
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $timestamp = now()->timestamp;
        $signature = 't=' . $timestamp . ',v1=' . hash_hmac('sha256', $timestamp . '.' . $payload, 'whsec_test');

        $server = $this->transformHeadersToServerVars([
            'Stripe-Signature' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ]);

        $this->call('POST', route('billing.stripe.webhook'), [], [], [], $server, $payload)
            ->assertOk();

        $this->assertSame(Organization::SUBSCRIPTION_PAST_DUE, $organization->fresh()->subscription_status);
    }
}
