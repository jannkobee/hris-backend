<?php

use App\Http\Controllers\LeaveConversionRequest\LeaveConversionRequestController;
use Illuminate\Support\Facades\Route;

Route::apiResource('/leave-conversion-requests', LeaveConversionRequestController::class);
Route::post('/leave-conversion-requests/{id}/approve', [LeaveConversionRequestController::class, 'approve']);
Route::post('/leave-conversion-requests/{id}/reject', [LeaveConversionRequestController::class, 'reject']);
