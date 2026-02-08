<?php

use App\Http\Controllers\Position\PositionController;
use Illuminate\Support\Facades\Route;

Route::apiResource('/positions', PositionController::class);
