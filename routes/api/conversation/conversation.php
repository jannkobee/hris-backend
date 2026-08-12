<?php

use App\Http\Controllers\Conversation\ConversationController;
use App\Http\Controllers\Message\MessageController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('conversations', [ConversationController::class, 'index']);
    Route::get('conversations/recipients', [ConversationController::class, 'recipients']);
    Route::post('conversations', [ConversationController::class, 'store']);
    Route::post('conversations/{conversation}/read', [ConversationController::class, 'markRead']);

    Route::get('conversations/{conversation}/messages', [MessageController::class, 'index']);
    Route::post('conversations/{conversation}/messages', [MessageController::class, 'store']);
});
