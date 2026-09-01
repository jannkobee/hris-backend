<?php

use App\Http\Controllers\Expense\ExpenseClaimController;
use Illuminate\Support\Facades\Route;

Route::prefix('expense-claims')->name('expense-claims.')->controller(ExpenseClaimController::class)->group(function (): void {
    Route::get('/', 'index')->name('index');
    Route::post('/', 'store')->name('store');
    Route::post('{claim}/review', 'review')->name('review');
    Route::post('{claim}/reimburse', 'reimburse')->name('reimburse');
});
