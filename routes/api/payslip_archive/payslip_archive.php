<?php

use App\Http\Controllers\Payroll\PayslipArchiveController;
use Illuminate\Support\Facades\Route;

Route::prefix('payslip-archives')->name('payslip-archives.')->controller(PayslipArchiveController::class)->group(function (): void {
    Route::post('items/{item}', 'store')->name('store');
    Route::get('{archive}/download', 'download')->name('download');
});
