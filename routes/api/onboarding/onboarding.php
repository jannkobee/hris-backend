<?php

use App\Http\Controllers\Platform\OrganizationOwnerInvitationController;
use Illuminate\Support\Facades\Route;

Route::prefix('onboarding')
    ->middleware('throttle:owner-invitation')
    ->name('onboarding.')
    ->controller(OrganizationOwnerInvitationController::class)
    ->group(function (): void {
        Route::post('owner-invitations/accept', 'accept')->name('owner-invitations.accept');
    });
