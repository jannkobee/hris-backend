<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::controller(AuthController::class)->name('auth.')->prefix('auth')->group(function () {
    Route::post('/login', 'login')->name('login');
    Route::post('/logout', 'logout')->middleware(['auth:sanctum', 'tenant.auth'])->name('logout');

    Route::get('/auth-user', 'authUser')->middleware(['auth:sanctum', 'tenant.auth'])->name('auth-user');
    Route::get('/settings', 'getSettings')->middleware(['auth:sanctum', 'tenant.auth'])->name('settings.index');
    Route::patch('/settings', 'updateSettings')->middleware(['auth:sanctum', 'tenant.auth'])->name('settings.update');
});
