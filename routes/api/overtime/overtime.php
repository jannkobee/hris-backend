<?php

use App\Http\Controllers\Overtime\OvertimeController;
use Illuminate\Support\Facades\Route;

Route::prefix('overtime')->name('overtime.')->controller(OvertimeController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/', 'store')->name('store');
    Route::get('/{id}', 'show')->name('show');
    Route::put('/{id}', 'update')->name('update');
    Route::delete('/{id}', 'destroy')->name('destroy');
    Route::post('/{id}/approve', 'approve')->name('approve');
    Route::post('/{id}/reject', 'reject')->name('reject');
});
