<?php

use App\Http\Controllers\Payroll\PayrollController;
use Illuminate\Support\Facades\Route;

Route::prefix('payroll-periods')->name('payroll.')->group(function (): void {
    Route::get('/', [PayrollController::class, 'index'])->name('index');
    Route::post('/', [PayrollController::class, 'store'])->name('store');
    Route::get('/{period}', [PayrollController::class, 'show'])->name('show');
    Route::delete('/{period}', [PayrollController::class, 'destroy'])->name('destroy');
    Route::post('/{period}/process', [PayrollController::class, 'process'])->name('process');
    Route::post('/{period}/acknowledge-exceptions', [PayrollController::class, 'acknowledgeAllExceptions'])->name('acknowledge-exceptions');
    Route::post('/{period}/approve', [PayrollController::class, 'approve'])->name('approve');
    Route::post('/{period}/mark-paid', [PayrollController::class, 'markPaid'])->name('mark-paid');
    Route::get('/{period}/export/csv', [PayrollController::class, 'exportCsv'])->name('export.csv');
});

Route::put('payroll-items/{item}', [PayrollController::class, 'updateItem'])->name('payroll-items.update');
Route::post('payroll-items/{item}/acknowledge-exceptions', [PayrollController::class, 'acknowledgeExceptions'])->name('payroll-items.acknowledge-exceptions');
