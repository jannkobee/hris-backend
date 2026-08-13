<?php

use App\Http\Controllers\RolePermission\RolePermissionController;
use Illuminate\Support\Facades\Route;

Route::prefix('role-permissions')->name('role-permissions.')->group(function () {
    Route::put('/{roleId}', [RolePermissionController::class, 'update'])
        ->middleware('permission:manage-role-permissions')
        ->name('update');
});
