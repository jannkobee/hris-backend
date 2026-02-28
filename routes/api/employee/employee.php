<?php

use App\Http\Controllers\Employee\EmployeeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])
    ->prefix('employees')
    ->name('employees.')
    ->group(function () {

        Route::get('generate-employee-number', [EmployeeController::class, 'generateEmployeeNo'])
            ->name('generate-employee-number');

        Route::apiResource('', EmployeeController::class)
            ->parameters(['' => 'employee']);
    });
