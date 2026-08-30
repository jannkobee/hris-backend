<?php

use App\Http\Controllers\LeaveBlackout\LeaveBlackoutDateController;
use Illuminate\Support\Facades\Route;

Route::apiResource('/leave-blackouts', LeaveBlackoutDateController::class)->except('show');
