<?php

use App\Http\Controllers\LeaveRequest\LeaveRequestController;
use Illuminate\Support\Facades\Route;

Route::apiResource('/leave-requests', LeaveRequestController::class)
    ->except(['destroy']);
Route::prefix('leave-requests')->controller(LeaveRequestController::class)->group(function (): void {
    Route::post('/{id}/approve', 'approve')->name('leave-requests.approve');
    Route::post('/{id}/reject', 'reject')->name('leave-requests.reject');
    Route::post('/{id}/cancel', 'cancel')->name('leave-requests.cancel');
    Route::get('/{id}/attachments/{attachment}', 'downloadAttachment')->name('leave-requests.attachments.download');
});
