<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\OidcExchangeRequest;
use App\Services\Auth\OidcService;
use App\Services\Utils\ResponseServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OidcController extends Controller
{
    private OidcService $oidcService;

    private ResponseServiceInterface $responseService;

    public function __construct(OidcService $oidcService, ResponseServiceInterface $responseService)
    {
        $this->oidcService = $oidcService;
        $this->responseService = $responseService;
    }

    public function redirect(string $organizationSlug): RedirectResponse
    {
        return redirect()->away($this->oidcService->authorizationUrl($organizationSlug));
    }

    public function callback(Request $request): RedirectResponse
    {
        $exchangeCode = $this->oidcService->complete($request);

        return redirect()->away(config('auth_security.frontend_url').'/login?'.http_build_query(['oidc_exchange' => $exchangeCode]));
    }

    public function exchange(OidcExchangeRequest $request)
    {
        return $this->responseService->resolveResponse('Login successful.', $this->oidcService->exchange($request->validated('exchange_code')));
    }
}
