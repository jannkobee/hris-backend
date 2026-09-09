<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\SubscriptionEvent;
use App\Services\Organizations\StripeBillingService;
use App\Services\Plans\PlatformPricingService;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BillingReliabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_zero_cost_subscription_restores_agreed_rate_without_introducing_a_minimum(): void
    {
        config(['billing.stripe.secret_key' => 'test']);
        $org = app(TenantContext::class)->organization();
        $org->update(['country_code' => 'PH', 'plan_code' => 'growth', 'billing_subscription_id' => 'sub_zero']);
        Http::fakeSequence()->push(['metadata' => ['free_employee_limit' => 10, 'minimum_billable_employees' => 0, 'growth_unit_amount' => 1900], 'items' => ['data' => [['id' => 'si_zero', 'quantity' => 1, 'price' => ['unit_amount' => 0, 'product' => 'prod_growth', 'currency' => 'php', 'recurring' => ['interval' => 'month']]]]]])->push([]);
        $this->assertSame(0, app(StripeBillingService::class)->syncSubscriptionQuantity($org));
        Http::assertSent(fn ($request) => $request->method() === 'POST' && $request['items'][0]['quantity'] === 0 && $request['items'][0]['price_data']['unit_amount'] === 1900);
    }

    public function test_sync_keeps_subscribed_allowance_and_throws_on_failed_update(): void
    {
        config(['billing.stripe.secret_key' => 'test']);
        $org = app(TenantContext::class)->organization();
        $org->update(['country_code' => 'PH', 'plan_code' => 'growth', 'billing_subscription_id' => 'sub_test']);
        for ($i = 0; $i < 4; $i++) {
            Employee::create(['employee_no' => 'SYNC-'.$i]);
        }
        app(PlatformPricingService::class)->update(['free_employee_limit' => 100]);
        $sequence = Http::fakeSequence()->push(['metadata' => ['free_employee_limit' => '1'], 'items' => ['data' => [['id' => 'si_test', 'quantity' => 1]]]])->push([], 200);
        $this->assertSame(3, app(StripeBillingService::class)->syncSubscriptionQuantity($org));
        Http::assertSent(fn ($request) => $request->method() === 'POST' && $request['items'][0]['quantity'] === 3);
        $sequence->push(['metadata' => ['free_employee_limit' => '1'], 'items' => ['data' => [['id' => 'si_test', 'quantity' => 1]]]])->push([], 500);
        $this->expectException(RequestException::class);
        app(StripeBillingService::class)->syncSubscriptionQuantity($org);
    }

    public function test_unpaid_checkout_is_ignored_and_paid_event_replay_is_idempotent(): void
    {
        config(['billing.stripe.webhook_secret' => 'test']);
        $org = app(TenantContext::class)->organization();
        $org->update(['subscription_status' => 'trialing']);
        $object = ['metadata' => ['organization_id' => $org->id, 'plan_code' => 'growth'], 'customer' => 'cus_test', 'subscription' => 'sub_test', 'payment_status' => 'unpaid'];
        $this->deliver('evt_unpaid', 'checkout.session.completed', $object);
        $this->assertSame('trialing', $org->fresh()->subscription_status);
        $object['payment_status'] = 'paid';
        $this->deliver('evt_paid', 'checkout.session.async_payment_succeeded', $object);
        $period = $org->fresh()->current_period_ends_at->toIso8601String();
        $this->travel(1)->days();
        $this->deliver('evt_paid', 'checkout.session.async_payment_succeeded', $object);
        $this->assertSame('active', $org->fresh()->subscription_status);
        $this->assertSame($period, $org->fresh()->current_period_ends_at->toIso8601String());
        $this->assertSame(1, SubscriptionEvent::where('reference', 'evt_paid')->count());
    }

    private function deliver(string $id, string $type, array $object): void
    {
        $payload = json_encode(compact('id', 'type') + ['data' => ['object' => $object]]);
        $request = Request::create('/', 'POST', [], [], [], [], $payload);
        $request->headers->set('Stripe-Signature', 't='.now()->timestamp.',v1='.hash_hmac('sha256', now()->timestamp.'.'.$payload, 'test'));
        app(StripeBillingService::class)->handleWebhook($request);
    }
}
