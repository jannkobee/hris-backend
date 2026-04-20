<?php

use App\Http\Controllers\LeaveType\LeaveTypeController;
use Illuminate\Support\Facades\Route;

Route::apiResource('/leave-types', LeaveTypeController::class);
