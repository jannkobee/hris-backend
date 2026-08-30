<?php

namespace App\Services\Integrations;

use App\Models\WebhookSubscription;
use Illuminate\Support\Facades\Http;
use Throwable;

class WebhookDispatcher
{
    public function dispatch(string $event, array $data): void
    {
        WebhookSubscription::query()
            ->where('is_active', true)
            ->whereJsonContains('event_types', $event)
            ->each(function (WebhookSubscription $subscription) use ($event, $data): void {
                $payload = json_encode([
                    'id' => (string) str()->uuid(),
                    'event' => $event,
                    'occurred_at' => now()->toIso8601String(),
                    'data' => $data,
                ], JSON_THROW_ON_ERROR);

                try {
                    if (! $this->safeUrl($subscription->url)) {
                        throw new \RuntimeException('Webhook URL must not use a private IP address.');
                    }

                    $response = Http::acceptJson()->timeout(5)->connectTimeout(3)
                        ->withHeaders([
                            'X-HRIS-Event' => $event,
                            'X-HRIS-Signature' => 'sha256='.hash_hmac('sha256', $payload, $subscription->signing_secret),
                        ])->withBody($payload, 'application/json')->post($subscription->url);

                    if (! $response->successful()) {
                        throw new \RuntimeException("Webhook responded with HTTP {$response->status()}.");
                    }

                    $subscription->update(['last_delivered_at' => now(), 'last_delivery_error' => null]);
                } catch (Throwable $exception) {
                    report($exception);
                    $subscription->update(['last_delivery_error' => $exception->getMessage()]);
                }
            });
    }

    private function safeUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (! is_string($host) || $host === '') {
            return false;
        }

        return ! filter_var($host, FILTER_VALIDATE_IP)
            || filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }
}
