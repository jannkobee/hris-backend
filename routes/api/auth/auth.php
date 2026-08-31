<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\OidcController;
use Illuminate\Support\Facades\Route;

Route::controller(AuthController::class)->name('auth.')->prefix('auth')->group(function () {
    Route::post('/login', 'login')->middleware('throttle:login')->name('login');
    Route::post('/mfa/challenge', 'verifyMfaChallenge')->middleware('throttle:mfa-challenge')->name('mfa.challenge');
    Route::post('/forgot-password', 'forgotPassword')->middleware('throttle:password-reset')->name('password.forgot');
    Route::post('/reset-password', 'resetPassword')->middleware('throttle:password-reset')->name('password.reset');
    Route::post('/logout', 'logout')->middleware(['auth:sanctum', 'tenant.auth'])->name('logout');

    Route::get('/auth-user', 'authUser')->middleware(['auth:sanctum', 'tenant.auth'])->name('auth-user');
    Route::get('/settings', 'getSettings')->middleware(['auth:sanctum', 'tenant.auth'])->name('settings.index');
    Route::patch('/settings', 'updateSettings')->middleware(['auth:sanctum', 'tenant.auth'])->name('settings.update');
    Route::post('/password', 'updatePassword')->middleware(['auth:sanctum', 'tenant.auth'])->name('password.update');
    Route::get('/mfa', 'mfaStatus')->middleware(['auth:sanctum', 'tenant.auth'])->name('mfa.status');
    Route::post('/mfa/setup', 'startMfaSetup')->middleware(['auth:sanctum', 'tenant.auth'])->name('mfa.setup');
    Route::post('/mfa/confirm', 'confirmMfa')->middleware(['auth:sanctum', 'tenant.auth'])->name('mfa.confirm');
    Route::delete('/mfa', 'disableMfa')->middleware(['auth:sanctum', 'tenant.auth'])->name('mfa.disable');
    Route::get('/sessions', 'sessions')->middleware(['auth:sanctum', 'tenant.auth'])->name('sessions.index');
    Route::delete('/sessions/others', 'revokeOtherSessions')->middleware(['auth:sanctum', 'tenant.auth'])->name('sessions.others.destroy');
    Route::delete('/sessions/{token}', 'revokeSession')->whereNumber('token')->middleware(['auth:sanctum', 'tenant.auth'])->name('sessions.destroy');
});

Route::controller(OidcController::class)->name('oidc.')->prefix('auth/oidc')->group(function () {
    Route::get('{organizationSlug}/redirect', 'redirect')->name('redirect');
    Route::get('callback', 'callback')->name('callback');
    Route::post('exchange', 'exchange')->middleware('throttle:login')->name('exchange');
});
