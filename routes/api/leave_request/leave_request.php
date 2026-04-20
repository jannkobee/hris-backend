<?php

use App\Http\Controllers\LeaveRequest\LeaveRequestController;
use Illuminate\Support\Facades\Route;

Route::apiResource('/leave-requests', LeaveRequestController::class);
