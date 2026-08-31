<?php

use App\Http\Controllers\PublicAPI\PublicAPIController;
use App\Http\Controllers\PublicAPI\TrialSignupController;
use Illuminate\Support\Facades\Route;

Route::prefix('public-apis')->name('public-apis.')->controller(PublicAPIController::class)->group(function () {
    Route::get('/countries', 'getCountries')->name('countries');
    Route::get('/pricing', 'pricing')->name('pricing');
    Route::get('/countries/{countryName}/states', 'getStatesByCountry')->name('states');
    Route::get('/countries/{countryName}/states/{stateName}/cities', 'getCitiesByState')->name('cities-by-state');
});

Route::prefix('public-apis')->name('public-apis.')->middleware('throttle:trial-signup')->controller(TrialSignupController::class)->group(function (): void {
    Route::post('trial-signups', 'store')->name('trial-signups.store');
});
