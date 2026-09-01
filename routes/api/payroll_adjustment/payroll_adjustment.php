<?php

use App\Http\Controllers\Payroll\PayrollAdjustmentRunController;
use Illuminate\Support\Facades\Route;

Route::prefix('payroll-adjustments')->name('payroll-adjustments.')->controller(PayrollAdjustmentRunController::class)->group(function (): void {
    Route::get('/', 'index')->name('index');
    Route::post('/', 'store')->name('store');
    Route::post('{run}/items', 'storeItem')->name('items.store');
    Route::post('{run}/lock', 'lock')->name('lock');
});
