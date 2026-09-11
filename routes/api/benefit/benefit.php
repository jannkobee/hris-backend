<?php

use App\Http\Controllers\Benefits\BenefitController;
use Illuminate\Support\Facades\Route;

Route::prefix('benefit-plans')->name('benefit-plans.')->controller(BenefitController::class)->group(function (): void {
    Route::get('/', 'index')->name('index');
    Route::post('/', 'store')->name('store');
    Route::get('de-minimis-ceilings', 'deMinimisCeilings')->name('de-minimis-ceilings');
    Route::patch('{plan}', 'update')->name('update');
    Route::get('{plan}/enrollments', 'enrollments')->name('enrollments.index');
    Route::post('{plan}/enrollments', 'enroll')->name('enrollments.store');
});

Route::prefix('benefit-enrollments')->name('benefit-enrollments.')->controller(BenefitController::class)->group(function (): void {
    Route::delete('{enrollment}', 'cancelEnrollment')->name('destroy');
});
