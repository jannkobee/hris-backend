<?php

use App\Http\Controllers\AppSetting\AppSettingController;
use Illuminate\Support\Facades\Route;

Route::name('app-settings.')->group(function (): void {
    Route::get('app-settings', [AppSettingController::class, 'index'])->name('index');
    Route::put('app-settings', [AppSettingController::class, 'update'])->name('update');
});
