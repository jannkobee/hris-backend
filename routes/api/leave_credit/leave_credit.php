<?php

use App\Http\Controllers\LeaveCredit\LeaveCreditController;
use Illuminate\Support\Facades\Route;

Route::apiResource('/leave-credits', LeaveCreditController::class);
