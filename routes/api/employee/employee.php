<?php

use App\Http\Controllers\Employee\EmployeeController;
use Illuminate\Support\Facades\Route;

Route::prefix('employees')
    ->name('employees.')
    ->controller(EmployeeController::class)
    ->group(function () {
        Route::get('employee-no/generate', 'generateEmployeeNo')
            ->name('employee-no.generate');

        Route::get('employee-no/settings', 'getNumberSettings')
            ->name('employee-no.settings.show');

        Route::put('employee-no/settings', 'updateNumberSettings')
            ->name('employee-no.settings.update');

        Route::post('employee-no/reformat', 'reformatEmployeeNumbers')
            ->name('employee-no.reformat');
    });

Route::apiResource('employees', EmployeeController::class);
