<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\PlatformOperationLog;
use App\Services\Organizations\StripeBillingService;
use App\Services\Plans\PlatformPricingService;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PlatformPricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_pricing_requires_staff_key_and_preserves_scheduled_versions(): void
    {
        config(['platform.provisioning_key' => 'pricing-test']);
        $data = ['free_employee_limit' => 15, 'growth_price_per_employee' => 2500, 'currency' => 'php'];
        $this->patchJson(route('platform.pricing.update'), $data)->assertUnauthorized();
        $this->withHeaders(['X-Platform-Provisioning-Key' => 'pricing-test'])
            ->patchJson(route('platform.pricing.update'), $data)->assertSuccessful();
        $this->getJson(route('public-pricing'))->assertOk()->assertJsonPath('data.free_employee_limit', 15);
        $this->patchJson(route('platform.pricing.update'), [...$data, 'growth_price_per_employee' => 3000, 'effective_at' => now()->addDay()->toIso8601String()])->assertSuccessful();
        $service = app(PlatformPricingService::class);
        $this->assertSame(2500, $service->current()['growth_price_per_employee']);
        $this->assertCount(2, $service->history());
        $this->travel(2)->days();
        $this->assertSame(3000, $service->current()['growth_price_per_employee']);
        $this->assertSame(2, PlatformOperationLog::where('action', 'platform pricing updated')->count());
        $this->patchJson(route('platform.pricing.update'), [...$data, 'currency' => 'usd'])->assertUnprocessable();
    }

    public function test_checkout_uses_active_headcount_and_saved_rate(): void
    {
        config(['billing.stripe.secret_key' => 'test']);
        Http::fake(['*' => Http::response(['id' => 'session', 'url' => 'https://example.test'])]);
        $organization = app(TenantContext::class)->organization();
        $organization->update(['country_code' => 'PH', 'plan_code' => 'enterprise']);
        app(PlatformPricingService::class)->update(['free_employee_limit' => 1, 'growth_price_per_employee' => 2300, 'currency' => 'php']);
        Employee::create(['employee_no' => 'ONE']);
        Employee::create(['employee_no' => 'TWO']);
        Employee::create(['employee_no' => 'THREE']);
        Employee::create(['employee_no' => 'ENDED', 'employment_effective_to' => now()->subDay()]);
        app(StripeBillingService::class)->checkout($organization, ['plan_code' => 'growth', 'billing_interval' => 'month', 'success_url' => 'https://example.test', 'cancel_url' => 'https://example.test']);
        Http::assertSent(fn($request) => $request['line_items'][0]['quantity'] === 2 && $request['line_items'][0]['price_data']['unit_amount'] === 2300);
    }
}
