<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePlatformPricingRequest;
use App\Services\Plans\PlatformPricingService;
use App\Services\Utils\ResponseServiceInterface;
use Illuminate\Http\JsonResponse;

class PlatformPricingController extends Controller
{
    private ResponseServiceInterface $response;

    private PlatformPricingService $pricing;

    public function __construct(ResponseServiceInterface $response, PlatformPricingService $pricing)
    {
        $this->response = $response;
        $this->pricing = $pricing;
    }

    public function show(): JsonResponse
    {
        return $this->response->successResponse('Platform pricing', $this->pricing->current());
    }

    public function update(UpdatePlatformPricingRequest $request): JsonResponse
    {
        return $this->response->updateResponse('Platform pricing', $this->pricing->update($request->validated()));
    }

    public function history(): JsonResponse
    {
        return $this->response->successResponse('Pricing history', $this->pricing->history());
    }
}
