<?php

use App\Http\Controllers\PublicAPI\PublicAPIController;
use Illuminate\Support\Facades\Route;

Route::prefix('public-apis')->name('public-apis.')->controller(PublicAPIController::class)->group(function () {
    // Countries
    Route::get('/countries', 'getCountries')->name('countries');
    Route::get('/countries/{iso2}', 'getCountry')->name('country');

    // States
    Route::get('/countries/{countryIso2}/states', 'getStatesByCountry')->name('states');
    Route::get('/countries/{countryIso2}/states/{stateIso2}', 'getState')->name('state');

    // Cities
    Route::get('/countries/{countryIso2}/cities', 'getCitiesByCountry')->name('cities-by-country');
    Route::get('/countries/{countryIso2}/states/{stateIso2}/cities', 'getCitiesByState')->name('cities-by-state');
});
