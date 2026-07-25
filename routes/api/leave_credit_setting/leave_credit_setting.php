<?php

use App\Http\Controllers\LeaveCreditSetting\LeaveCreditSettingController;
use Illuminate\Support\Facades\Route;

Route::apiResource('/leave-credit-settings', LeaveCreditSettingController::class);
