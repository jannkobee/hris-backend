<?php

use App\Http\Controllers\ScheduledTask\ScheduledTaskController;
use Illuminate\Support\Facades\Route;

Route::middleware('plan:automation')->group(function (): void {
    Route::apiResource('scheduled-tasks', ScheduledTaskController::class);
    Route::prefix('scheduled-tasks')->controller(ScheduledTaskController::class)->group(function (): void {
        Route::post('{id}/run', 'runNow');
    });
});
