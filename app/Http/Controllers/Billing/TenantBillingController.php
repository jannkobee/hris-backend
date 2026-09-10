<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateCheckoutSessionRequest;
use App\Models\Employee;
use App\Services\AuditLog\AuditLogServiceInterface;
use App\Services\Organizations\StripeBillingService;
use App\Services\Plans\PlatformPricingService;
use App\Services\Utils\ResponseServiceInterface;
use App\Tenancy\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantBillingController extends Controller
{
    private StripeBillingService $stripe;

    private TenantContext $tenantContext;

    private AuditLogServiceInterface $auditLogs;

    private ResponseServiceInterface $response;

    private PlatformPricingService $platformPricing;

    public function __construct(
        StripeBillingService $stripe,
        TenantContext $tenantContext,
        AuditLogServiceInterface $auditLogs,
        ResponseServiceInterface $response,
        PlatformPricingService $platformPricing
    ) {
        $this->stripe = $stripe;
        $this->tenantContext = $tenantContext;
        $this->auditLogs = $auditLogs;
        $this->response = $response;
        $this->platformPricing = $platformPricing;
        $this->middleware('permission:manage-organization-settings');
    }

    public function summary(Request $request): JsonResponse
    {
        $organization = $this->tenantContext->organization();
        $today = now($organization->timezone)->toDateString();

        $activeCount = Employee::query()->where('organization_id', $organization->id)
            ->where(function ($query) use ($today) {
                $query->whereNull('employment_effective_to')->orWhereDate('employment_effective_to', '>=', $today);
            })->count();

        $pricing = $this->platformPricing->current();
        $freeLimit = (int) ($pricing['free_employee_limit'] ?? 10);
        $growthPricePerEmployee = (int) ($pricing['growth_price_per_employee'] ?? 1900);
        $minimum = (int) ($pricing['minimum_billable_employees'] ?? 0);
        $billableCount = max($organization->plan_code === 'growth' ? $minimum : 0, $activeCount - $freeLimit);
        $monthlyAmountCentavos = $billableCount * $growthPricePerEmployee;

        return $this->response->successResponse('Billing summary', [
            'plan_code' => $organization->plan_code,
            'plan_name' => config("plans.plans.{$organization->plan_code}.name", 'Basic'),
            'subscription_status' => $organization->subscription_status,
            'trial_ends_at' => $organization->trial_ends_at?->toIso8601String(),
            'current_period_ends_at' => $organization->current_period_ends_at?->toIso8601String(),
            'billing_provider' => $organization->billing_provider,
            'billing_customer_id' => $organization->billing_customer_id,
            'billing_subscription_id' => $organization->billing_subscription_id,
            'active_employee_count' => $activeCount,
            'free_employee_limit' => $freeLimit,
            'minimum_billable_employees' => $minimum,
            'billable_employee_count' => $billableCount,
            'growth_price_per_employee' => $growthPricePerEmployee,
            'growth_currency' => $pricing['currency'] ?? 'php',
            'monthly_amount' => $monthlyAmountCentavos,
        ]);
    }

    public function checkout(CreateCheckoutSessionRequest $request): JsonResponse
    {
        if ($request->user()?->role?->name !== 'Admin') {
            throw new AuthorizationException('Only the organization administrator can initiate subscription checkout.');
        }

        $organization = $this->tenantContext->organization();
        $session = $this->stripe->checkout($organization, $request->validated());

        $this->auditLogs->insertLog($organization, 'create checkout session', [
            'plan_code' => $request->validated('plan_code'),
            'provider' => 'stripe',
        ]);

        return $this->response->storeResponse('Checkout session', $session);
    }
}
