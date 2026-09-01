<?php

use App\Http\Controllers\Holiday\HolidayController;
use Illuminate\Support\Facades\Route;

Route::apiResource('/holidays', HolidayController::class);
Route::post('/holidays/import', [HolidayController::class, 'import'])
    ->middleware('permission:manage-holidays')
    ->name('holidays.import');
