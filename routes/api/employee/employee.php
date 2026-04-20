<?php

use App\Http\Controllers\Employee\EmployeeController;
use Illuminate\Support\Facades\Route;

Route::prefix('employees')
    ->name('employees.')
    ->group(function () {
        Route::get('employee-no/generate', [EmployeeController::class, 'generateEmployeeNo'])
            ->name('employee-no.generate');

        Route::get('employee-no/settings', [EmployeeController::class, 'getNumberSettings'])
            ->name('employee-no.settings.show');

        Route::put('employee-no/settings', [EmployeeController::class, 'updateNumberSettings'])
            ->name('employee-no.settings.update');

        Route::post('employee-no/reformat', [EmployeeController::class, 'reformatEmployeeNumbers'])
            ->name('employee-no.reformat');
    });

Route::apiResource('employees', EmployeeController::class);
