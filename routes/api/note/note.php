<?php

use App\Http\Controllers\Note\NoteController;
use Illuminate\Support\Facades\Route;

Route::middleware('plan:notes')->group(function (): void {
    Route::apiResource('notes', NoteController::class);
});
