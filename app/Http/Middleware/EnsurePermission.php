<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->hasAnyPermission($permissions)) {
            throw new AuthorizationException('You do not have permission to perform this action.');
        }

        return $next($request);
    }
}
