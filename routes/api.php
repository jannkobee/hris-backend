<?php

use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json([
    'status' => 'ok',
    'timestamp' => now()->toIso8601String(),
]))->name('health');

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
        'realtime/realtime',
    ];

    foreach ($routes as $route) {
        Route::group([], base_path("routes/api/{$route}.php"));
    }
});

// Authenticated
Route::middleware(['auth:sanctum', 'tenant.auth'])->group(function () {
    Route::post('/broadcasting/auth', [\Illuminate\Broadcasting\BroadcastController::class, 'authenticate']);

    $routes = [
        'user/user',
        'profile/profile',
        'dashboard/dashboard',
        'navigation/navigation',
        'conversation/conversation',
        'note/note',
        'role/role',
        'permission/permission',
        'role_permission/role_permission',
        'department/department',
        'position/position',
        'employee/employee',
        'employee_document/employee_document',
        'employment_status/employment_status',
        'job_grade/job_grade',
        'attendance/attendance',
        'announcement/announcement',
        'scheduled_task/scheduled_task',
        'leave_request/leave_request',
        'leave_type/leave_type',
        'leave_credit/leave_credit',
        'leave_credit_setting/leave_credit_setting',
        'leave_conversion_request/leave_conversion_request',
        'overtime/overtime',
        'audit_log/audit_log',
        'app_setting/app_setting',
        'payroll/payroll',
        'workplace_hub/workplace_hub',
        'holiday/holiday',
    ];

    foreach ($routes as $route) {
        Route::group([], base_path("routes/api/{$route}.php"));
    }
});
