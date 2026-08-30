<?php

use App\Http\Controllers\Attendance\AttendanceController;
use App\Http\Controllers\Attendance\AttendanceCorrectionController;
use App\Http\Controllers\Shift\ShiftAssignmentController;
use App\Http\Controllers\Shift\ShiftTemplateController;
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
Route::prefix('attendance-corrections')->name('attendance-corrections.')->controller(AttendanceCorrectionController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/', 'store')->name('store');
    Route::patch('{attendanceCorrection}/review', 'review')->name('review');
});
Route::apiResource('shift-templates', ShiftTemplateController::class);
Route::apiResource('shift-assignments', ShiftAssignmentController::class);
