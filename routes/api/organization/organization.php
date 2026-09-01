<?php

use App\Http\Controllers\Organization\OrganizationBrandingController;
use Illuminate\Support\Facades\Route;

Route::prefix('organization/branding')
    ->name('organization.branding.')
    ->controller(OrganizationBrandingController::class)
    ->group(function (): void {
        Route::get('/', 'show')->name('show');
        Route::get('/logo', 'logo')->name('logo.show');
        Route::post('/logo', 'uploadLogo')->name('logo.upload');
        Route::delete('/logo', 'deleteLogo')->name('logo.delete');
    });
