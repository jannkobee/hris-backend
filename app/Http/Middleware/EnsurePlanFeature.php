<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\Plans\PlanEntitlementService;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlanFeature
{
    public function __construct(private readonly PlanEntitlementService $entitlements)
    {
    }

    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $this->entitlements->allows($user->organization, $feature)) {
            throw new AuthorizationException(
                'This feature is not included in your organization\'s subscription plan.'
            );
        }

        return $next($request);
    }
}
