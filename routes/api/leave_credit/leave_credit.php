<?php

use App\Http\Controllers\LeaveCredit\LeaveCreditController;
use Illuminate\Support\Facades\Route;

Route::get('/leave-credits/forecast', [LeaveCreditController::class, 'forecast'])->name('leave-credits.forecast');

Route::apiResource('/leave-credits', LeaveCreditController::class)
    ->only(['index', 'show']);
