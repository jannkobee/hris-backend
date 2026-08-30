<?php

use App\Http\Controllers\Dashboard\DashboardController;
use Illuminate\Support\Facades\Route;

Route::controller(DashboardController::class)->group(function (): void {
    Route::get('/dashboard/overview', 'overview')->name('dashboard.overview');
    Route::get('/dashboard/analytics', 'analytics')->middleware('permission:view-reports')->name('dashboard.analytics');
});
