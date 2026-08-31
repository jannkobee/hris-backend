<?php

use App\Http\Controllers\Platform\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('billing/stripe/webhook', [StripeWebhookController::class, 'handle'])->name('billing.stripe.webhook');
