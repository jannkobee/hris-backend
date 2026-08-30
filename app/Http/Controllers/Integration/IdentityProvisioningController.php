<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use App\Http\Requests\ScimTokenRequest;
use App\Http\Requests\SsoConfigurationRequest;
use App\Models\ScimToken;
use App\Models\SsoConfiguration;
use App\Services\Utils\ResponseServiceInterface;
use Illuminate\Http\Request;

class IdentityProvisioningController extends Controller
{
    private ResponseServiceInterface $response;

    public function __construct(ResponseServiceInterface $response)
    {
        $this->response = $response;
        $this->middleware('permission:manage-sso');
    }

    public function showSso()
    {
        return $this->response->successResponse('SSO configuration', SsoConfiguration::query()->first());
    }

    public function updateSso(SsoConfigurationRequest $request)
    {
        $configuration = SsoConfiguration::query()->firstOrNew();
        $configuration->fill(['provider' => 'oidc', ...$request->validated()])->save();

        return $this->response->updateResponse('SSO configuration', $configuration->fresh());
    }

    public function indexScimTokens()
    {
        return $this->response->successResponse('SCIM tokens', ScimToken::query()->latest()->get(['id', 'name', 'last_used_at', 'expires_at', 'created_at']));
    }

    public function storeScimToken(ScimTokenRequest $request)
    {
        $data = $request->validated();
        $plainToken = 'scim_'.str()->random(64);
        $token = ScimToken::create([
            'name' => $data['name'],
            'token_hash' => hash('sha256', $plainToken),
            'created_by' => $request->user()->id,
            'expires_at' => isset($data['expires_in_days']) ? now()->addDays($data['expires_in_days']) : null,
        ]);

        return $this->response->storeResponse('SCIM token', ['token' => $token, 'plain_text_token' => $plainToken]);
    }

    public function destroyScimToken(Request $request, ScimToken $scimToken)
    {
        abort_unless($scimToken->created_by === $request->user()->id || $request->user()->hasPermission('manage-users'), 403);
        $scimToken->delete();

        return $this->response->deleteResponse('SCIM token', true);
    }
}
