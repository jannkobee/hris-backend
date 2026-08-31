<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Services\Organizations\StripeBillingService;
use Illuminate\Http\Request;

class StripeWebhookController extends Controller
{
    private StripeBillingService $stripe;

    public function __construct(StripeBillingService $stripe)
    {
        $this->stripe = $stripe;
    }

    public function handle(Request $request)
    {
        $this->stripe->handleWebhook($request);

        return response()->json(['received' => true]);
    }
}
