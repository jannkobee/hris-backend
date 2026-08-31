<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListPlatformOrganizationsRequest;
use App\Http\Requests\ProvisionOrganizationRequest;
use App\Http\Requests\RevokeOrganizationCredentialsRequest;
use App\Http\Requests\UpdateOrganizationStatusRequest;
use App\Http\Requests\UpdateOrganizationSubscriptionRequest;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\ScimToken;
use App\Models\SsoConfiguration;
use App\Models\SubscriptionEvent;
use App\Models\User;
use App\Models\WebhookSubscription;
use App\Services\Organizations\OrganizationProvisioningService;
use App\Services\Organizations\PlatformSupportService;
use App\Services\Organizations\SubscriptionLifecycleService;
use App\Services\Plans\PlanEntitlementService;
use App\Services\Utils\ResponseServiceInterface;
use App\Tenancy\TenantContext;

class OrganizationProvisioningController extends Controller
{
    private OrganizationProvisioningService $provisioning;

    private PlanEntitlementService $entitlements;

    private ResponseServiceInterface $responseService;

    private TenantContext $tenantContext;

    private PlatformSupportService $support;

    private SubscriptionLifecycleService $subscriptions;

    public function __construct(
        OrganizationProvisioningService $provisioning,
        PlanEntitlementService $entitlements,
        ResponseServiceInterface $responseService,
        TenantContext $tenantContext,
        PlatformSupportService $support,
        SubscriptionLifecycleService $subscriptions,
    ) {
        $this->provisioning = $provisioning;
        $this->entitlements = $entitlements;
        $this->responseService = $responseService;
        $this->tenantContext = $tenantContext;
        $this->support = $support;
        $this->subscriptions = $subscriptions;
    }

    public function store(ProvisionOrganizationRequest $request)
    {
        $organization = $this->provisioning->provision($request->validated());

        return $this->responseService->storeResponse(
            'Organization',
            $this->payload($organization)
        );
    }

    public function index(ListPlatformOrganizationsRequest $request)
    {
        $filters = $request->validated();
        $organizations = Organization::query()
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('slug', 'like', "%{$search}%"));
            })
            ->when($filters['plan_code'] ?? null, fn ($query, string $plan) => $query->where('plan_code', $plan))
            ->when($filters['subscription_status'] ?? null, fn ($query, string $status) => $query->where('subscription_status', $status))
            ->orderBy('name')
            ->paginate((int) ($filters['per_page'] ?? 20));

        $organizations->getCollection()->transform(fn (Organization $organization): array => $this->listPayload($organization));

        return $this->responseService->successResponse('Organizations', $organizations);
    }

    public function updateSubscription(UpdateOrganizationSubscriptionRequest $request, Organization $organization)
    {
        $organization = $this->provisioning->updateSubscription($organization, $request->validated());

        return $this->responseService->updateResponse(
            'Organization subscription',
            $this->payload($organization)
        );
    }

    public function show(Organization $organization)
    {
        $details = $this->tenantContext->run($organization, function () use ($organization): array {
            return [
                ...$this->listPayload($organization),
                'plan' => $this->entitlements->payload($organization),
                'subscription_events' => SubscriptionEvent::query()->latest()->limit(20)->get(),
                'identity' => [
                    'sso_active' => SsoConfiguration::query()->where('is_active', true)->exists(),
                    'active_scim_tokens' => ScimToken::query()
                        ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                        ->count(),
                ],
                'webhooks' => WebhookSubscription::query()
                    ->select(['id', 'name', 'event_types', 'is_active', 'last_delivered_at', 'last_delivery_error'])
                    ->latest()
                    ->get(),
            ];
        });

        return $this->responseService->successResponse('Organization', $details);
    }

    public function updateStatus(UpdateOrganizationStatusRequest $request, Organization $organization)
    {
        return $this->responseService->updateResponse('Organization status', $this->payload($this->support->setStatus($organization, $request->validated('status'))));
    }

    public function revokeCredentials(RevokeOrganizationCredentialsRequest $request, Organization $organization)
    {
        return $this->responseService->resolveResponse('Organization credentials revoked.', $this->support->revokeCredentials($organization, $request->validated('scope')));
    }

    public function reconcileSubscription(Organization $organization)
    {
        $updated = $this->tenantContext->run($organization, fn (): ?Organization => $this->subscriptions->reconcile($organization));

        return $this->responseService->resolveResponse(
            $updated ? 'Subscription reconciled and updated.' : 'Subscription is already current.',
            $this->payload($updated ?: $organization->fresh())
        );
    }

    public function subscriptionEvents(Organization $organization)
    {
        $events = $this->tenantContext->run($organization, fn () => SubscriptionEvent::query()->latest()->get());

        return $this->responseService->successResponse('Subscription events', $events);
    }

    private function payload(Organization $organization): array
    {
        return [
            ...$organization->toArray(),
            'plan' => $this->entitlements->payload($organization),
        ];
    }

    private function listPayload(Organization $organization): array
    {
        return $this->tenantContext->run($organization, function () use ($organization): array {
            $employeeCount = Employee::query()->count();
            $admin = User::query()->whereHas('role', fn ($query) => $query->where('name', 'Admin'))->orderBy('created_at')->first();
            $limit = $this->entitlements->employeeLimit($organization);

            return [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
                'country_code' => $organization->country_code,
                'timezone' => $organization->timezone,
                'status' => $organization->status,
                'plan_code' => $organization->plan_code,
                'subscription_status' => $organization->subscription_status,
                'trial_ends_at' => $organization->trial_ends_at?->toIso8601String(),
                'current_period_ends_at' => $organization->current_period_ends_at?->toIso8601String(),
                'billing' => [
                    'provider' => $organization->billing_provider,
                    'customer_id' => $organization->billing_customer_id,
                    'subscription_id' => $organization->billing_subscription_id,
                    'interval' => $organization->billing_interval,
                ],
                'usage' => [
                    'employees' => $employeeCount,
                    'employee_limit' => $limit,
                    'percentage' => $limit ? round($employeeCount / $limit * 100, 1) : null,
                ],
                'administrator' => $admin ? ['name' => $admin->full_name, 'email' => $admin->email] : null,
                'created_at' => $organization->created_at?->toIso8601String(),
            ];
        });
    }
}
