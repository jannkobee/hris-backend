<?php

use App\Http\Controllers\LeaveConversionRequest\LeaveConversionRequestController;
use Illuminate\Support\Facades\Route;

Route::apiResource('/leave-conversion-requests', LeaveConversionRequestController::class);
Route::prefix('leave-conversion-requests')->controller(LeaveConversionRequestController::class)->group(function (): void {
    Route::post('/{id}/approve', 'approve');
    Route::post('/{id}/reject', 'reject');
});
