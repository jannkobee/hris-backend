<?php

use App\Http\Controllers\Realtime\RealtimeConfigController;
use Illuminate\Support\Facades\Route;

Route::controller(RealtimeConfigController::class)->group(function (): void {
    Route::get('/realtime/config', 'show');
});
