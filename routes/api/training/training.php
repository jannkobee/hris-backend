<?php

use App\Http\Controllers\Training\TrainingController;
use Illuminate\Support\Facades\Route;

Route::prefix('training-courses')->name('training-courses.')->controller(TrainingController::class)->group(function (): void {
    Route::get('/', 'index')->name('index');
    Route::post('/', 'store')->name('store');
    Route::post('{course}/enrollments', 'enroll')->name('enrollments.store');
    Route::post('enrollments/{enrollment}/complete', 'complete')->name('enrollments.complete');
});
