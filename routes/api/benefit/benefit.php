<?php

use App\Http\Controllers\Benefits\BenefitController;
use Illuminate\Support\Facades\Route;

Route::prefix('benefit-plans')->name('benefit-plans.')->controller(BenefitController::class)->group(function (): void {
    Route::get('/', 'index')->name('index');
    Route::post('/', 'store')->name('store');
    Route::post('{plan}/enrollments', 'enroll')->name('enrollments.store');
});
