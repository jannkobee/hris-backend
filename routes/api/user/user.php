<?php

use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/users/template', [UserController::class, 'downloadTemplate']);
Route::post('/users/import', [UserController::class, 'import']);

Route::apiResource('/users', UserController::class);
