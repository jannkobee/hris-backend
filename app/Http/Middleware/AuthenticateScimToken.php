<?php

namespace App\Http\Middleware;

use App\Models\ScimToken;
use App\Services\Plans\PlanEntitlementService;
use App\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateScimToken
{
    private TenantContext $tenantContext;

    private PlanEntitlementService $entitlements;

    public function __construct(TenantContext $tenantContext, PlanEntitlementService $entitlements)
    {
        $this->tenantContext = $tenantContext;
        $this->entitlements = $entitlements;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $token = preg_replace('/^Bearer\s+/i', '', (string) $request->header('Authorization'));
        $scimToken = ScimToken::withoutGlobalScopes()->where('token_hash', hash('sha256', $token))->first();

        if (! $scimToken || ($scimToken->expires_at && $scimToken->expires_at->isPast())) {
            return response()->json(['detail' => 'Invalid SCIM bearer token.'], Response::HTTP_UNAUTHORIZED);
        }

        $organization = $scimToken->organization;
        if (! $organization?->isActive()) {
            return response()->json(['detail' => 'Organization is not active.'], Response::HTTP_FORBIDDEN);
        }

        if (! $this->entitlements->allows($organization, 'sso_scim')) {
            return response()->json(['detail' => 'SCIM is not included in this organization\'s subscription plan.'], Response::HTTP_FORBIDDEN);
        }

        $request->attributes->set('organization', $organization);
        $scimToken->forceFill(['last_used_at' => now()])->saveQuietly();

        return $this->tenantContext->run($organization, fn (): Response => $next($request));
    }
}
