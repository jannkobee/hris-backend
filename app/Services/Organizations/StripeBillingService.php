<?php

namespace App\Services\Organizations;

use App\Models\Employee;
use App\Models\Organization;
use App\Services\Plans\PlatformPricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class StripeBillingService
{
    private SubscriptionLifecycleService $subscriptions;

    private PlatformPricingService $platformPricing;

    public function __construct(SubscriptionLifecycleService $subscriptions, PlatformPricingService $platformPricing)
    {
        $this->subscriptions = $subscriptions;
        $this->platformPricing = $platformPricing;
    }

    public function checkout(Organization $organization, array $data): array
    {
        if ((string) config('billing.stripe.secret_key') === '') {
            throw ValidationException::withMessages(['billing' => 'Stripe checkout is not configured.']);
        }

        $plan = $data['plan_code'];
        $pricing = $this->pricing($organization);
        $amount = (int) data_get($pricing, "prices.{$plan}.{$data['billing_interval']}", 0);
        $quantity = 1;
        $snapshot = [];
        if ($plan === 'growth' && strtoupper((string) $organization->country_code) === 'PH') {
            $snapshot = $this->platformPricing->current();
            $count = Employee::withoutGlobalScopes()->where('organization_id', $organization->id)
                ->where(fn($query) => $query->whereNull('employment_effective_to')->orWhereDate('employment_effective_to', '>=', now($organization->timezone)->toDateString()))->count();
            $quantity = max(0, $count - $snapshot['free_employee_limit']);
            if ($quantity === 0) {
                $quantity = max(1, (int) ($data['additional_employees'] ?? 1));
            }
        }
        if ($amount < 1) {
            throw ValidationException::withMessages(['plan_code' => 'This plan is not available for self-service checkout.']);
        }

        $response = Http::asForm()->acceptJson()->timeout(20)
            ->withBasicAuth((string) config('billing.stripe.secret_key'), '')
            ->post(rtrim((string) config('billing.stripe.api_base'), '/') . '/v1/checkout/sessions', [
                'mode' => 'subscription',
                'success_url' => $data['success_url'] . '?checkout=success',
                'cancel_url' => $data['cancel_url'] . '?checkout=cancelled',
                'client_reference_id' => $organization->id,
                'customer' => $organization->billing_customer_id,
                'customer_email' => $organization->billing_customer_id ? null : ($data['billing_email'] ?? null),
                'line_items' => [[
                    'quantity' => $quantity,
                    'price_data' => [
                        'currency' => $pricing['currency'],
                        'unit_amount' => $amount,
                        'product_data' => ['name' => 'HRIS ' . config("plans.plans.{$plan}.name")],
                        'recurring' => ['interval' => $data['billing_interval']],
                    ],
                ]],
                'metadata' => ['organization_id' => $organization->id, 'plan_code' => $plan, 'billing_interval' => $data['billing_interval'], 'pricing_version' => $snapshot['version'] ?? 'legacy', 'free_employee_limit' => $snapshot['free_employee_limit'] ?? 0],
                'subscription_data' => ['metadata' => ['organization_id' => $organization->id, 'plan_code' => $plan, 'billing_interval' => $data['billing_interval'], 'pricing_version' => $snapshot['version'] ?? 'legacy', 'free_employee_limit' => $snapshot['free_employee_limit'] ?? 0]],
            ])->throw()->json();

        return ['id' => $response['id'] ?? null, 'url' => $response['url'] ?? null];
    }

    public function portal(Organization $organization, string $returnUrl): array
    {
        if ((string) config('billing.stripe.secret_key') === '') {
            throw ValidationException::withMessages(['billing' => 'Stripe billing is not configured.']);
        }
        if (! $organization->billing_customer_id) {
            throw ValidationException::withMessages(['billing' => 'No Stripe customer is linked to this organization yet.']);
        }

        $response = Http::asForm()->acceptJson()->timeout(20)
            ->withBasicAuth((string) config('billing.stripe.secret_key'), '')
            ->post(rtrim((string) config('billing.stripe.api_base'), '/') . '/v1/billing_portal/sessions', [
                'customer' => $organization->billing_customer_id,
                'return_url' => $returnUrl,
            ])
            ->throw()
            ->json();

        return ['url' => $response['url'] ?? null];
    }

    public function syncSubscriptionQuantity(Organization $organization): ?int
    {
        if ((string) config('billing.stripe.secret_key') === '' || ! $organization->billing_subscription_id) {
            return null;
        }

        if ($organization->plan_code !== 'growth' || strtoupper((string) $organization->country_code) !== 'PH') {
            return null;
        }

        $snapshot = $this->platformPricing->current();
        $count = Employee::withoutGlobalScopes()->where('organization_id', $organization->id)
            ->where(fn($query) => $query->whereNull('employment_effective_to')->orWhereDate('employment_effective_to', '>=', now($organization->timezone)->toDateString()))->count();
        $newQuantity = max(1, $count - $snapshot['free_employee_limit']);

        try {
            $subUrl = rtrim((string) config('billing.stripe.api_base'), '/') . '/v1/subscriptions/' . $organization->billing_subscription_id;
            $subscription = Http::asForm()->acceptJson()->timeout(15)
                ->withBasicAuth((string) config('billing.stripe.secret_key'), '')
                ->get($subUrl)->json();

            $itemId = data_get($subscription, 'items.data.0.id');
            if ($itemId) {
                Http::asForm()->acceptJson()->timeout(15)
                    ->withBasicAuth((string) config('billing.stripe.secret_key'), '')
                    ->post($subUrl, [
                        'items' => [
                            ['id' => $itemId, 'quantity' => $newQuantity],
                        ],
                        'proration_behavior' => 'create_prorations',
                    ]);
            }

            return $newQuantity;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function handleWebhook(Request $request): void
    {
        $payload = $request->getContent();
        $this->verifySignature($payload, (string) $request->header('Stripe-Signature'));
        $event = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        $object = $event['data']['object'] ?? [];
        $type = $event['type'] ?? '';

        if ($type === 'checkout.session.completed') {
            $organization = Organization::query()->find($object['metadata']['organization_id'] ?? $object['client_reference_id'] ?? null);
            if ($organization) {
                $plan = $object['metadata']['plan_code'] ?? $organization->plan_code;
                $interval = $object['metadata']['billing_interval'] ?? 'month';
                $this->subscriptions->update($organization, [
                    'plan_code' => $plan,
                    'subscription_status' => Organization::SUBSCRIPTION_ACTIVE,
                    'trial_ends_at' => null,
                    'current_period_ends_at' => $interval === 'year' ? now()->addYearNoOverflow() : now()->addMonthNoOverflow(),
                    'employee_limit' => $organization->employee_limit,
                ], 'stripe', $event['id'] ?? null);
                $organization->update(['billing_provider' => 'stripe', 'billing_customer_id' => $object['customer'] ?? null, 'billing_subscription_id' => $object['subscription'] ?? null, 'billing_interval' => $interval]);
            }
        }

        if (in_array($type, ['customer.subscription.updated', 'customer.subscription.deleted'], true)) {
            $organization = Organization::query()->where('billing_provider', 'stripe')->where('billing_subscription_id', $object['id'] ?? null)->first();
            if ($organization) {
                $status = match ($object['status'] ?? null) {
                    'active', 'trialing' => Organization::SUBSCRIPTION_ACTIVE,
                    'past_due', 'unpaid' => Organization::SUBSCRIPTION_PAST_DUE,
                    'canceled', 'incomplete_expired' => Organization::SUBSCRIPTION_CANCELLED,
                    default => $organization->subscription_status,
                };
                $this->subscriptions->update($organization, [
                    'plan_code' => $organization->plan_code,
                    'subscription_status' => $status,
                    'trial_ends_at' => $organization->trial_ends_at,
                    'current_period_ends_at' => isset($object['current_period_end']) ? now()->setTimestamp((int) $object['current_period_end']) : $organization->current_period_ends_at,
                    'employee_limit' => $organization->employee_limit,
                ], 'stripe', $event['id'] ?? null);
            }
        }

        if ($type === 'invoice.payment_failed') {
            $subId = $object['subscription'] ?? null;
            if ($subId) {
                $organization = Organization::query()->where('billing_provider', 'stripe')->where('billing_subscription_id', $subId)->first();
                if ($organization) {
                    $this->subscriptions->update($organization, [
                        'plan_code' => $organization->plan_code,
                        'subscription_status' => Organization::SUBSCRIPTION_PAST_DUE,
                        'trial_ends_at' => $organization->trial_ends_at,
                        'current_period_ends_at' => $organization->current_period_ends_at,
                        'employee_limit' => $organization->employee_limit,
                    ], 'stripe', $event['id'] ?? null);
                }
            }
        }
    }

    private function verifySignature(string $payload, string $signature): void
    {
        $secret = (string) config('billing.stripe.webhook_secret');
        if ($secret === '') {
            abort(503, 'Stripe webhook verification is not configured.');
        }

        $parts = collect(explode(',', $signature))->mapWithKeys(function (string $part): array {
            [$key, $value] = array_pad(explode('=', $part, 2), 2, null);

            return [$key => $value];
        });
        $timestamp = (int) $parts->get('t');
        $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);

        if ($timestamp < now()->subMinutes(5)->timestamp || ! hash_equals($expected, (string) $parts->get('v1'))) {
            abort(400, 'Invalid Stripe webhook signature.');
        }
    }

    private function pricing(Organization $organization): array
    {
        $pricing = config('billing.regional_prices.' . $organization->country_code, [
            'currency' => config('billing.currency'),
            'prices' => config('billing.stripe.prices'),
        ]);
        if (strtoupper((string) $organization->country_code) === 'PH') {
            $dynamic = $this->platformPricing->current();
            $pricing['currency'] = $dynamic['currency'];
            $pricing['prices']['growth'] = ['month' => $dynamic['growth_price_per_employee'], 'year' => $dynamic['growth_price_per_employee'] * 10];
        }

        return $pricing;
    }
}
