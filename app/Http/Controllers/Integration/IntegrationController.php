<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApiTokenRequest;
use App\Http\Requests\WebhookSubscriptionRequest;
use App\Models\User;
use App\Models\WebhookSubscription;
use App\Services\Utils\ResponseServiceInterface;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class IntegrationController extends Controller
{
    private ResponseServiceInterface $response;

    public function __construct(ResponseServiceInterface $response)
    {
        $this->response = $response;
        $this->middleware('permission:manage-integrations');
    }

    public function indexTokens(Request $request)
    {
        return $this->response->successResponse('API tokens', $request->user()->tokens()
            ->latest()->get(['id', 'name', 'abilities', 'last_used_at', 'expires_at', 'created_at']));
    }

    public function storeToken(ApiTokenRequest $request)
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();
        $expiresAt = isset($data['expires_in_days']) ? now()->addDays($data['expires_in_days']) : null;
        $token = $user->createToken($data['name'], $data['abilities'], $expiresAt);

        return $this->response->storeResponse('API token', [
            'id' => $token->accessToken->id,
            'name' => $token->accessToken->name,
            'abilities' => $token->accessToken->abilities,
            'expires_at' => $token->accessToken->expires_at,
            'plain_text_token' => $token->plainTextToken,
        ]);
    }

    public function destroyToken(Request $request, string $tokenId)
    {
        $token = PersonalAccessToken::query()->whereKey($tokenId)
            ->where('tokenable_type', User::class)
            ->where('tokenable_id', $request->user()->id)
            ->firstOrFail();
        $token->delete();

        return $this->response->deleteResponse('API token', true);
    }

    public function indexWebhooks()
    {
        return $this->response->successResponse('Webhook subscriptions', WebhookSubscription::query()
            ->with('creator:id,first_name,last_name')->latest()->get());
    }

    public function storeWebhook(WebhookSubscriptionRequest $request)
    {
        $subscription = WebhookSubscription::create($request->validated() + [
            'created_by' => $request->user()->id,
            'signing_secret' => str()->random(64),
        ]);

        return $this->response->storeResponse('Webhook subscription', [
            'subscription' => $subscription,
            'signing_secret' => $subscription->signing_secret,
        ]);
    }

    public function updateWebhook(WebhookSubscriptionRequest $request, WebhookSubscription $webhookSubscription)
    {
        $webhookSubscription->update($request->validated());

        return $this->response->updateResponse('Webhook subscription', $webhookSubscription->fresh());
    }

    public function destroyWebhook(WebhookSubscription $webhookSubscription)
    {
        $webhookSubscription->delete();

        return $this->response->deleteResponse('Webhook subscription', true);
    }
}
