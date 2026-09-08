<?php

use App\Http\Controllers\Billing\BillingPortalController;
use App\Http\Controllers\Billing\TenantBillingController;
use Illuminate\Support\Facades\Route;

Route::prefix('billing')
    ->name('billing.')
    ->group(function (): void {
        Route::controller(TenantBillingController::class)->group(function (): void {
            Route::get('summary', 'summary')->name('summary');
            Route::post('checkout-sessions', 'checkout')->name('checkout-sessions.store');
        });

        Route::controller(BillingPortalController::class)->group(function (): void {
            Route::post('portal-sessions', 'store')->name('portal-sessions.store');
        });
    });
