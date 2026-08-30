<?php

use App\Http\Controllers\Profile\ProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('profile')->name('profile.')->controller(ProfileController::class)->group(function (): void {
    Route::get('/', 'show')->name('show');
    Route::put('/', 'update')->name('update');
    Route::post('/photo', 'uploadPhoto')->name('photo.upload');
    Route::delete('/photo', 'deletePhoto')->name('photo.delete');
});

Route::controller(ProfileController::class)->group(function (): void {
    Route::get('/users/{user}/profile-photo', 'photo')->name('users.profile-photo');
});
