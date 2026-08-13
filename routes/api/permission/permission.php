<?php

use App\Http\Controllers\Permission\PermissionController;
use Illuminate\Support\Facades\Route;

Route::get('/permissions', [PermissionController::class, 'index'])
    ->middleware('permission:manage-role-permissions')
    ->name('permissions.index');
