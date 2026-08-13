<?php

use App\Http\Controllers\Profile\ProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('profile')->name('profile.')->group(function (): void {
    Route::get('/', [ProfileController::class, 'show'])->name('show');
    Route::put('/', [ProfileController::class, 'update'])->name('update');
    Route::post('/photo', [ProfileController::class, 'uploadPhoto'])->name('photo.upload');
    Route::delete('/photo', [ProfileController::class, 'deletePhoto'])->name('photo.delete');
});

Route::get('/users/{user}/profile-photo', [ProfileController::class, 'photo'])->name('users.profile-photo');
