<?php

use App\Http\Controllers\WorkplaceHub\MeetingActionItemController;
use App\Http\Controllers\WorkplaceHub\MeetingAttachmentController;
use App\Http\Controllers\WorkplaceHub\MeetingController;
use App\Http\Controllers\WorkplaceHub\MeetingRoomController;
use Illuminate\Support\Facades\Route;

Route::prefix('workplace-hub')->middleware(['plan:workplace_hub', 'permission:view-workplace-hub'])->name('workplace.')->group(function (): void {
    Route::controller(MeetingController::class)->group(function (): void {
        Route::get('/people', 'people')->name('people');
        Route::get('/meetings', 'index')->name('meetings.index');
        Route::post('/meetings', 'store')->name('meetings.store');
        Route::get('/meetings/{meeting}', 'show')->name('meetings.show');
        Route::put('/meetings/{meeting}', 'update')->name('meetings.update');
        Route::post('/meetings/{meeting}/complete', 'complete')->name('meetings.complete');
        Route::delete('/meetings/{meeting}', 'destroy')->name('meetings.destroy');
    });

    Route::controller(MeetingRoomController::class)->group(function (): void {
        Route::get('/rooms', 'index')->name('rooms.index');
        Route::post('/rooms', 'store')->name('rooms.store');
        Route::put('/rooms/{room}', 'update')->name('rooms.update');
        Route::delete('/rooms/{room}', 'destroy')->name('rooms.destroy');
    });

    Route::controller(MeetingAttachmentController::class)->group(function (): void {
        Route::post('/meetings/{meeting}/attachments', 'store')->name('attachments.store');
        Route::get('/attachments/{attachment}/download', 'download')->name('attachments.download');
        Route::delete('/attachments/{attachment}', 'destroy')->name('attachments.destroy');
    });

    Route::controller(MeetingActionItemController::class)->group(function (): void {
        Route::post('/meetings/{meeting}/action-items', 'store')->name('action-items.store');
        Route::put('/action-items/{actionItem}', 'update')->name('action-items.update');
        Route::delete('/action-items/{actionItem}', 'destroy')->name('action-items.destroy');
    });
});
