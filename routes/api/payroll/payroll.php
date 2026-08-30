<?php

use App\Http\Controllers\Payroll\PayrollController;
use Illuminate\Support\Facades\Route;

Route::middleware('plan:payroll')->group(function (): void {
    Route::prefix('payroll-periods')->name('payroll.')->controller(PayrollController::class)->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{period}', 'show')->name('show');
        Route::delete('/{period}', 'destroy')->name('destroy');
        Route::post('/{period}/process', 'process')->name('process');
        Route::post('/{period}/acknowledge-exceptions', 'acknowledgeAllExceptions')->name('acknowledge-exceptions');
        Route::post('/{period}/approve', 'approve')->name('approve');
        Route::post('/{period}/mark-paid', 'markPaid')->name('mark-paid');
        Route::get('/{period}/export/csv', 'exportCsv')->name('export.csv');
    });

    Route::controller(PayrollController::class)->group(function (): void {
        Route::put('payroll-items/{item}', 'updateItem')->name('payroll-items.update');
        Route::post('payroll-items/{item}/acknowledge-exceptions', 'acknowledgeExceptions')->name('payroll-items.acknowledge-exceptions');
    });
});
