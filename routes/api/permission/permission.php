<?php

use App\Http\Controllers\Permission\PermissionController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:manage-role-permissions')->controller(PermissionController::class)->group(function (): void {
    Route::get('/permissions', 'index')->name('permissions.index');
    Route::get('/permission-presets', 'presets')->name('permission-presets.index');
});
