<?php

use App\Http\Controllers\PublicAPI\PublicAPIController;
use Illuminate\Support\Facades\Route;

Route::prefix('public-apis')->name('public-apis.')->controller(PublicAPIController::class)->group(function () {
    Route::get('/countries', 'getCountries')->name('countries');
    Route::get('/countries/{countryName}/states', 'getStatesByCountry')->name('states');
    Route::get('/countries/{countryName}/states/{stateName}/cities', 'getCitiesByState')->name('cities-by-state');
});
