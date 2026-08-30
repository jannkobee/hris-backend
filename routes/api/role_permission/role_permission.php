<?php

use App\Http\Controllers\RolePermission\RolePermissionController;
use Illuminate\Support\Facades\Route;

Route::prefix('role-permissions')->name('role-permissions.')->controller(RolePermissionController::class)->group(function () {
    Route::put('/{roleId}', 'update')
        ->middleware('permission:manage-role-permissions')
        ->name('update');
});
