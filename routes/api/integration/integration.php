<?php

use App\Http\Controllers\Integration\IdentityProvisioningController;
use App\Http\Controllers\Integration\IntegrationController;
use Illuminate\Support\Facades\Route;

Route::prefix('integrations')->name('integrations.')->middleware('plan:integrations')->controller(IntegrationController::class)->group(function (): void {
    Route::get('tokens', 'indexTokens')->name('tokens.index');
    Route::post('tokens', 'storeToken')->name('tokens.store');
    Route::delete('tokens/{tokenId}', 'destroyToken')->name('tokens.destroy');

    Route::get('webhooks', 'indexWebhooks')->name('webhooks.index');
    Route::post('webhooks', 'storeWebhook')->name('webhooks.store');
    Route::put('webhooks/{webhookSubscription}', 'updateWebhook')->name('webhooks.update');
    Route::delete('webhooks/{webhookSubscription}', 'destroyWebhook')->name('webhooks.destroy');
});

Route::prefix('identity')->name('identity.')->middleware('plan:sso_scim')->controller(IdentityProvisioningController::class)->group(function (): void {
    Route::get('sso', 'showSso')->name('sso.show');
    Route::put('sso', 'updateSso')->name('sso.update');
    Route::get('scim-tokens', 'indexScimTokens')->name('scim-tokens.index');
    Route::post('scim-tokens', 'storeScimToken')->name('scim-tokens.store');
    Route::delete('scim-tokens/{scimToken}', 'destroyScimToken')->name('scim-tokens.destroy');
});
