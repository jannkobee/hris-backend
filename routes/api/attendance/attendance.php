<?php

use App\Http\Controllers\Attendance\AttendanceController;
use Illuminate\Support\Facades\Route;

Route::prefix('attendances')
    ->name('attendances.')
    ->group(function () {
        Route::post('time-in', [AttendanceController::class, 'timeIn'])->name('time-in');
        Route::post('time-out', [AttendanceController::class, 'timeOut'])->name('time-out');
        Route::get('today', [AttendanceController::class, 'today'])->name('today');
        Route::get('history', [AttendanceController::class, 'history'])->name('history');
        Route::get('{attendance}/photos/{type}', [AttendanceController::class, 'photo'])->name('photo');
    });

Route::apiResource('attendances', AttendanceController::class);
