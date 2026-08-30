<?php

use App\Http\Controllers\Conversation\ConversationController;
use App\Http\Controllers\Message\MessageController;
use Illuminate\Support\Facades\Route;

Route::prefix('conversations')->controller(ConversationController::class)->group(function (): void {
    Route::get('/', 'index');
    Route::get('/recipients', 'recipients');
    Route::post('/', 'store');
    Route::get('/{conversation}', 'show');
    Route::post('/{conversation}/read', 'markRead');
});

Route::prefix('conversations/{conversation}')->controller(MessageController::class)->group(function (): void {
    Route::get('/messages', 'index');
    Route::post('/messages', 'store');
    Route::get('/attachments/{attachment}', 'attachment');
});
