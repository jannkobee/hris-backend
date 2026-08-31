<?php

use App\Http\Controllers\Scim\ScimUserController;
use Illuminate\Support\Facades\Route;

Route::prefix('scim/v2')->name('scim.')->middleware('scim.auth')->controller(ScimUserController::class)->group(function (): void {
    Route::get('Users', 'index')->name('users.index');
    Route::post('Users', 'store')->name('users.store');
    Route::get('Users/{userId}', 'show')->name('users.show');
    Route::put('Users/{userId}', 'update')->name('users.update');
    Route::patch('Users/{userId}', 'patch')->name('users.patch');
});
