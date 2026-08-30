<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformProvisioningKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredKey = (string) config('platform.provisioning_key');
        $providedKey = (string) $request->header('X-Platform-Provisioning-Key');

        if ($configuredKey === '' || $providedKey === '' || ! hash_equals($configuredKey, $providedKey)) {
            throw new AuthenticationException('Invalid platform provisioning credentials.');
        }

        return $next($request);
    }
}
