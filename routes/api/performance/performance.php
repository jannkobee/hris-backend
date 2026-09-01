<?php

use App\Http\Controllers\Performance\PerformanceGoalController;
use App\Http\Controllers\Performance\PerformanceReviewController;
use Illuminate\Support\Facades\Route;

Route::prefix('performance-goals')->name('performance-goals.')->controller(PerformanceGoalController::class)->group(function (): void {
    Route::get('/', 'index')->name('index');
    Route::post('/', 'store')->name('store');
});

Route::prefix('performance-reviews')->name('performance-reviews.')->controller(PerformanceReviewController::class)->group(function (): void {
    Route::get('/', 'index')->name('index');
    Route::post('/', 'store')->name('store');
    Route::post('{review}/finalize', 'finalize')->name('finalize');
});
