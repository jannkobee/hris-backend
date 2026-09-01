<?php

use App\Http\Controllers\Billing\BillingPortalController;
use Illuminate\Support\Facades\Route;

Route::prefix('billing')
    ->name('billing.')
    ->controller(BillingPortalController::class)
    ->group(function (): void {
        Route::post('portal-sessions', 'store')->name('portal-sessions.store');
    });
