<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateBillingPortalSessionRequest;
use App\Services\AuditLog\AuditLogServiceInterface;
use App\Services\Organizations\StripeBillingService;
use App\Services\Utils\ResponseServiceInterface;
use App\Tenancy\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;

class BillingPortalController extends Controller
{
    private StripeBillingService $stripe;

    private TenantContext $tenantContext;

    private AuditLogServiceInterface $auditLogs;

    private ResponseServiceInterface $response;

    public function __construct(
        StripeBillingService $stripe,
        TenantContext $tenantContext,
        AuditLogServiceInterface $auditLogs,
        ResponseServiceInterface $response,
    ) {
        $this->stripe = $stripe;
        $this->tenantContext = $tenantContext;
        $this->auditLogs = $auditLogs;
        $this->response = $response;
        $this->middleware('permission:manage-organization-settings');
    }

    public function store(CreateBillingPortalSessionRequest $request)
    {
        if ($request->user()?->role?->name !== 'Admin') {
            throw new AuthorizationException('Only the organization owner can manage billing.');
        }
        $returnUrl = $request->validated('return_url');
        $this->ensureAllowedReturnUrl($returnUrl);
        $organization = $this->tenantContext->organization();
        $session = $this->stripe->portal($organization, $returnUrl);
        $this->auditLogs->insertLog($organization, 'open billing portal', [
            'provider' => $organization->billing_provider,
        ]);

        return $this->response->storeResponse('Billing portal session', $session);
    }

    private function ensureAllowedReturnUrl(string $returnUrl): void
    {
        $host = strtolower((string) parse_url($returnUrl, PHP_URL_HOST));
        $allowedHosts = array_filter(array_map('trim', (array) config('billing.portal_return_hosts', [])));

        if ($host === '' || ! in_array($host, $allowedHosts, true)) {
            throw new AuthorizationException('The billing portal return URL is not allowed.');
        }
    }
}
