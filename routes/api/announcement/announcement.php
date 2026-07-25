<?php

use App\Http\Controllers\Announcement\AnnouncementController;
use Illuminate\Support\Facades\Route;

Route::apiResource('announcements', AnnouncementController::class);
