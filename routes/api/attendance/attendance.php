<?php

use App\Http\Controllers\Attendance\AttendanceController;
use Illuminate\Support\Facades\Route;

Route::prefix('attendances')
    ->name('attendances.')
    ->controller(AttendanceController::class)
    ->group(function () {
        Route::post('time-in', 'timeIn')->name('time-in');
        Route::post('time-out', 'timeOut')->name('time-out');
        Route::get('today', 'today')->name('today');
        Route::get('history', 'history')->name('history');
        Route::get('{attendance}/photos/{type}', 'photo')->name('photo');
    });

Route::apiResource('attendances', AttendanceController::class);
