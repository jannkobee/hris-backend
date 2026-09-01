<?php

use App\Http\Controllers\Payroll\PayrollController;
use App\Http\Controllers\Payroll\StatutoryRuleController;
use Illuminate\Support\Facades\Route;

Route::middleware('plan:payroll')->group(function (): void {
    Route::prefix('payroll-periods')->name('payroll.')->controller(PayrollController::class)->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->middleware('permission:manage-payroll')->name('store');
        Route::get('/{period}', 'show')->name('show');
        Route::delete('/{period}', 'destroy')->middleware('permission:manage-payroll')->name('destroy');
        Route::post('/{period}/process', 'process')->middleware('permission:manage-payroll')->name('process');
        Route::post('/{period}/acknowledge-exceptions', 'acknowledgeAllExceptions')->middleware('permission:manage-payroll')->name('acknowledge-exceptions');
        Route::post('/{period}/approve', 'approve')->middleware('permission:approve-payroll')->name('approve');
        Route::post('/{period}/lock', 'lock')->middleware('permission:approve-payroll')->name('lock');
        Route::post('/{period}/mark-paid', 'markPaid')->middleware('permission:mark-payroll-paid')->name('mark-paid');
        Route::get('/{period}/export/csv', 'exportCsv')->middleware('permission:view-payroll')->name('export.csv');
    });

    Route::controller(PayrollController::class)->group(function (): void {
        Route::put('payroll-items/{item}', 'updateItem')->middleware('permission:manage-payroll')->name('payroll-items.update');
        Route::post('payroll-items/{item}/acknowledge-exceptions', 'acknowledgeExceptions')->middleware('permission:manage-payroll')->name('payroll-items.acknowledge-exceptions');
    });

    Route::apiResource('statutory-rules', StatutoryRuleController::class)->except('show');
});
