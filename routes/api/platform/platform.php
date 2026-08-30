<?php

use App\Http\Controllers\Platform\OrganizationProvisioningController;
use Illuminate\Support\Facades\Route;

Route::middleware(['platform.provisioning', 'throttle:platform-provisioning'])
    ->prefix('platform/organizations')
    ->name('platform.organizations.')
    ->controller(OrganizationProvisioningController::class)
    ->group(function (): void {
        Route::post('/', 'store')->name('store');
        Route::patch('/{organization}/subscription', 'updateSubscription')->name('subscription.update');
    });
