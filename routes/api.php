<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Unauthenticated
Route::middleware([])->group(function () {
    $routes = [
        'auth/auth',
        'public_api/public_api',
        'leave_type/leave_type',
        'leave_request/leave_request',
        'leave_credit/leave_credit',
        'leave_credit_setting/leave_credit_setting',
        'leave_conversion_request/leave_conversion_request',
    ];

    foreach ($routes as $route) {
        Route::group([], base_path("routes/api/{$route}.php"));
    }
});

// Authenticated
Route::middleware(['auth:sanctum'])->group(function () {
    $routes = [
        'user/user',
        'role/role',
        'permission/permission',
        'role_permission/role_permission',
        'department/department',
        'position/position',
        'employee/employee',
        'employment_status/employment_status',
        'job_grade/job_grade',
        'attendance/attendance',
        'announcement/announcement',
    ];

    foreach ($routes as $route) {
        Route::group([], base_path("routes/api/{$route}.php"));
    }
});
