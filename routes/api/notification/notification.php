<?php
use App\Http\Controllers\Notification\AppNotificationController; use Illuminate\Support\Facades\Route;
Route::get('/notifications',[AppNotificationController::class,'index'])->name('notifications.index'); Route::patch('/notifications/{notification}/read',[AppNotificationController::class,'read'])->name('notifications.read');
