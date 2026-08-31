<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateCheckoutSessionRequest;
use App\Models\Organization;
use App\Services\Organizations\StripeBillingService;
use App\Services\Utils\ResponseServiceInterface;

class BillingCheckoutController extends Controller
{
    private StripeBillingService $stripe;

    private ResponseServiceInterface $response;

    public function __construct(StripeBillingService $stripe, ResponseServiceInterface $response)
    {
        $this->stripe = $stripe;
        $this->response = $response;
    }

    public function store(CreateCheckoutSessionRequest $request, Organization $organization)
    {
        return $this->response->storeResponse('Checkout session', $this->stripe->checkout($organization, $request->validated()));
    }
}
