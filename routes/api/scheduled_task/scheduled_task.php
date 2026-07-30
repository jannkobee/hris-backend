<?php

/**
 * Add these lines to your existing routes/api.php, inside whatever
 * middleware group your other authenticated resources (Position, etc.)
 * already live in.
 */

use App\Http\Controllers\ScheduledTask\ScheduledTaskController;
use Illuminate\Support\Facades\Route;

Route::apiResource('scheduled-tasks', ScheduledTaskController::class);
Route::post('scheduled-tasks/{id}/run', [ScheduledTaskController::class, 'runNow']);
