<?php

use App\Http\Controllers\Dashboard\DashboardController;
use Illuminate\Support\Facades\Route;

Route::controller(DashboardController::class)->group(function (): void {
    Route::get('/dashboard/overview', 'overview')->name('dashboard.overview');
    Route::get('/dashboard/layout', 'layout')->name('dashboard.layout');
    Route::put('/dashboard/layout', 'updateLayout')->name('dashboard.layout.update');
    Route::get('/dashboard/analytics', 'analytics')->middleware('permission:view-reports')->name('dashboard.analytics');
    Route::get('/dashboard/analytics/export', 'exportAnalytics')->middleware('permission:view-reports')->name('dashboard.analytics.export');
});
