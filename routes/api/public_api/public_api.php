<?php

use App\Http\Controllers\PublicAPI\PublicAPIController;
use Illuminate\Support\Facades\Route;

Route::prefix('public-apis')->name('public-apis.')->controller(PublicAPIController::class)->group(function () {
    // Countries
    Route::get('/countries', 'getCountries')->name('countries');

    // States (Now accepts country full name instead of ISO2)
    Route::get('/countries/{countryName}/states', 'getStatesByCountry')->name('states');

    // Cities (Now accepts country full name and state full name instead of ISO2 codes)
    Route::get('/countries/{countryName}/states/{stateName}/cities', 'getCitiesByState')->name('cities-by-state');
});
