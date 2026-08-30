<?php

use App\Http\Controllers\AppSetting\AppSettingController;
use Illuminate\Support\Facades\Route;

Route::name('app-settings.')->controller(AppSettingController::class)->group(function (): void {
    Route::get('app-settings', 'index')->name('index');
    Route::put('app-settings', 'update')->name('update');
});
