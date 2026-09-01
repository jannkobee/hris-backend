<?php

use App\Http\Controllers\Payroll\StatutoryReportController;
use Illuminate\Support\Facades\Route;

Route::prefix('statutory-reports')->name('statutory-reports.')->controller(StatutoryReportController::class)->group(function (): void {
    Route::get('/', 'index')->name('index');
    Route::post('/', 'store')->middleware('permission:manage-payroll')->name('store');
});
