<?php

use App\Http\Controllers\Realtime\RealtimeConfigController;
use Illuminate\Support\Facades\Route;

Route::get('/realtime/config', [RealtimeConfigController::class, 'show']);
