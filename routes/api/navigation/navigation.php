<?php

use App\Http\Controllers\Navigation\NavigationBadgeController;
use Illuminate\Support\Facades\Route;

Route::get('/navigation/badges', NavigationBadgeController::class)->name('navigation.badges');
