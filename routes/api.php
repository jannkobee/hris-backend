<?php

use App\Http\Controllers\PublicAPI\PublicAPIController;
use Illuminate\Support\Facades\Http;
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
    ];

    foreach ($routes as $route) {
        Route::group([], base_path("routes/api/{$route}.php"));
    }
});
