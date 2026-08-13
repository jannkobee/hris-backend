<?php

use App\Http\Controllers\WorkplaceHub\MeetingActionItemController;
use App\Http\Controllers\WorkplaceHub\MeetingAttachmentController;
use App\Http\Controllers\WorkplaceHub\MeetingController;
use App\Http\Controllers\WorkplaceHub\MeetingRoomController;
use Illuminate\Support\Facades\Route;

Route::prefix('workplace-hub')->middleware('permission:view-workplace-hub')->name('workplace.')->group(function (): void {
    Route::get('/people', [MeetingController::class, 'people'])->name('people');

    Route::get('/rooms', [MeetingRoomController::class, 'index'])->name('rooms.index');
    Route::post('/rooms', [MeetingRoomController::class, 'store'])->name('rooms.store');
    Route::put('/rooms/{room}', [MeetingRoomController::class, 'update'])->name('rooms.update');
    Route::delete('/rooms/{room}', [MeetingRoomController::class, 'destroy'])->name('rooms.destroy');

    Route::get('/meetings', [MeetingController::class, 'index'])->name('meetings.index');
    Route::post('/meetings', [MeetingController::class, 'store'])->name('meetings.store');
    Route::get('/meetings/{meeting}', [MeetingController::class, 'show'])->name('meetings.show');
    Route::put('/meetings/{meeting}', [MeetingController::class, 'update'])->name('meetings.update');
    Route::post('/meetings/{meeting}/complete', [MeetingController::class, 'complete'])->name('meetings.complete');
    Route::delete('/meetings/{meeting}', [MeetingController::class, 'destroy'])->name('meetings.destroy');

    Route::post('/meetings/{meeting}/attachments', [MeetingAttachmentController::class, 'store'])->name('attachments.store');
    Route::get('/attachments/{attachment}/download', [MeetingAttachmentController::class, 'download'])->name('attachments.download');
    Route::delete('/attachments/{attachment}', [MeetingAttachmentController::class, 'destroy'])->name('attachments.destroy');

    Route::post('/meetings/{meeting}/action-items', [MeetingActionItemController::class, 'store'])->name('action-items.store');
    Route::put('/action-items/{actionItem}', [MeetingActionItemController::class, 'update'])->name('action-items.update');
    Route::delete('/action-items/{actionItem}', [MeetingActionItemController::class, 'destroy'])->name('action-items.destroy');
});
