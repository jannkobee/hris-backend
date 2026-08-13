<?php

use App\Http\Controllers\Dashboard\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard/overview', [DashboardController::class, 'overview'])->name('dashboard.overview');
