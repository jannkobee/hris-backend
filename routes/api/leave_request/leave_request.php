<?php

use App\Http\Controllers\LeaveRequest\LeaveRequestController;
use Illuminate\Support\Facades\Route;

Route::apiResource('/leave-requests', LeaveRequestController::class)
    ->except(['destroy']);
Route::post('/leave-requests/{id}/approve', [LeaveRequestController::class, 'approve'])->name('leave-requests.approve');
Route::post('/leave-requests/{id}/reject', [LeaveRequestController::class, 'reject'])->name('leave-requests.reject');
Route::post('/leave-requests/{id}/cancel', [LeaveRequestController::class, 'cancel'])->name('leave-requests.cancel');
Route::get('/leave-requests/{id}/attachments/{attachment}', [LeaveRequestController::class, 'downloadAttachment'])->name('leave-requests.attachments.download');
