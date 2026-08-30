<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Services\Tenancy\TenantResolver;
use App\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function __construct(
        private readonly TenantResolver $resolver,
        private readonly TenantContext $context
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('health', 'public-apis.*', 'platform.*')) {
            return $next($request);
        }

        $organization = $this->resolver->resolve($request);

        if (! $organization) {
            return $this->error('Organization not found.', Response::HTTP_NOT_FOUND);
        }

        if (! $organization->isActive()) {
            return $this->error('Organization is not active.', Response::HTTP_FORBIDDEN);
        }

        $request->attributes->set('organization', $organization);

        return $this->context->run($organization, fn (): Response => $next($request));
    }

    private function error(string $message, int $status): JsonResponse
    {
        return response()->json(['message' => $message], $status);
    }
}
