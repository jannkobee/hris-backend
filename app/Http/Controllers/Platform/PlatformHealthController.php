<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\PlatformHealthHistoryRequest;
use App\Http\Requests\UpdatePlatformHealthSettingsRequest;
use App\Http\Requests\UpdatePlatformMaintenanceRequest;
use App\Services\Organizations\PlatformHealthService;
use App\Services\Utils\ResponseServiceInterface;
use Illuminate\Http\JsonResponse;

class PlatformHealthController extends Controller
{
    private ResponseServiceInterface $response;

    private PlatformHealthService $health;

    public function __construct(ResponseServiceInterface $response, PlatformHealthService $health)
    {
        $this->response = $response;
        $this->health = $health;
    }

    public function show(): JsonResponse
    {
        return $this->response->successResponse('Platform health', $this->health->health());
    }

    public function history(PlatformHealthHistoryRequest $request): JsonResponse
    {
        return $this->response->successResponse(
            'Platform health history',
            $this->health->history((int) ($request->validated()['limit'] ?? 12))
        );
    }

    public function settings(): JsonResponse
    {
        return $this->response->successResponse('Platform health settings', $this->health->settings());
    }

    public function updateSettings(UpdatePlatformHealthSettingsRequest $request): JsonResponse
    {
        return $this->response->updateResponse(
            'Platform health settings',
            $this->health->updateSettings($request->validated())
        );
    }

    public function updateMaintenance(UpdatePlatformMaintenanceRequest $request): JsonResponse
    {
        $attributes = $request->validated();

        return $this->response->updateResponse(
            'Platform maintenance status',
            $this->health->setMaintenance(
                $attributes['enabled'],
                $attributes['retry_after'] ?? null,
                $attributes['reason'] ?? null
            )
        );
    }
}
