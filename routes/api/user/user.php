<?php

use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('users')->controller(UserController::class)->group(function (): void {
    Route::get('/template', 'downloadTemplate');
    Route::post('/import', 'import');
});

Route::apiResource('/users', UserController::class);
