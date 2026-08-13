<?php

namespace App\Http\Controllers\Realtime;

use App\Http\Controllers\Controller;
use App\Services\AppSettings\AppSettingService;
use Illuminate\Http\JsonResponse;

class RealtimeConfigController extends Controller
{
    /**
     * Reverb's app key is intentionally public: browser clients need it to
     * open a WebSocket. Keep the app secret server-side.
     */
    public function show(AppSettingService $settings): JsonResponse
    {
        $enabled = $settings->get('messaging.realtime_enabled', true)
            && config('broadcasting.default') === 'reverb'
            && filled(config('broadcasting.connections.reverb.key'));

        return response()->json([
            'data' => [
                'enabled' => $enabled,
                'key' => $enabled ? config('broadcasting.connections.reverb.key') : null,
                'host' => env('REVERB_PUBLIC_HOST', config('broadcasting.connections.reverb.options.host')),
                'port' => (int) env('REVERB_PUBLIC_PORT', config('broadcasting.connections.reverb.options.port')),
                'scheme' => env('REVERB_PUBLIC_SCHEME', config('broadcasting.connections.reverb.options.scheme')),
            ],
        ]);
    }
}
