<?php

use App\Http\Controllers\Platform\BillingCheckoutController;
use App\Http\Controllers\Platform\OrganizationOwnerInvitationController;
use App\Http\Controllers\Platform\OrganizationProvisioningController;
use App\Http\Controllers\Platform\PlatformHealthController;
use App\Http\Controllers\Platform\PlatformPricingController;
use App\Http\Controllers\Platform\PlatformSessionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['platform.provisioning', 'throttle:platform-provisioning'])
    ->prefix('platform')->name('platform.')->controller(PlatformPricingController::class)->group(function (): void {
        Route::get('pricing', 'show')->name('pricing.show');
        Route::get('pricing/history', 'history')->name('pricing.history');
        Route::patch('pricing', 'update')->name('pricing.update');
    });

Route::middleware(['platform.provisioning', 'throttle:platform-provisioning'])
    ->prefix('platform')
    ->name('platform.')
    ->controller(PlatformSessionController::class)
    ->group(function (): void {
        Route::get('session', 'show')->name('session.show');
    });

Route::middleware(['platform.provisioning', 'throttle:platform-provisioning'])
    ->prefix('platform')
    ->name('platform.')
    ->controller(PlatformHealthController::class)
    ->group(function (): void {
        Route::get('health', 'show')->name('health.show');
        Route::get('health/history', 'history')->name('health.history');
        Route::get('health/settings', 'settings')->name('health.settings');
        Route::patch('health/settings', 'updateSettings')->name('health.settings.update');
        Route::patch('maintenance', 'updateMaintenance')->name('maintenance.update');
    });

Route::middleware(['platform.provisioning', 'throttle:platform-provisioning'])
    ->prefix('platform/organizations')
    ->name('platform.organizations.')
    ->controller(OrganizationOwnerInvitationController::class)
    ->group(function (): void {
        Route::post('/{organization}/owner-invitations', 'store')->name('owner-invitations.store');
    });

Route::middleware(['platform.provisioning', 'throttle:platform-provisioning'])
    ->prefix('platform/organizations')
    ->name('platform.organizations.')
    ->controller(OrganizationProvisioningController::class)
    ->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{organization}', 'show')->name('show');
        Route::patch('/{organization}/status', 'updateStatus')->name('status.update');
        Route::post('/{organization}/subscription/reconcile', 'reconcileSubscription')->name('subscription.reconcile');
        Route::post('/{organization}/credentials/revoke', 'revokeCredentials')->name('credentials.revoke');
        Route::patch('/{organization}/subscription', 'updateSubscription')->name('subscription.update');
        Route::get('/{organization}/subscription/events', 'subscriptionEvents')->name('subscription.events');
    });

Route::middleware(['platform.provisioning', 'throttle:platform-provisioning'])
    ->prefix('platform/organizations')
    ->name('platform.organizations.')
    ->controller(BillingCheckoutController::class)
    ->group(function (): void {
        Route::post('/{organization}/checkout-sessions', 'store')->name('checkout-sessions.store');
    });
