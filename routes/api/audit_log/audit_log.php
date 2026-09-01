<?php

use App\Http\Controllers\AuditLog\AuditLogController;
use Illuminate\Support\Facades\Route;

Route::prefix('audit-logs')->middleware('plan:audit_logs')->name('audit-logs.')->controller(AuditLogController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/export/csv', 'export')->middleware('permission:view-audit-logs')->name('export.csv');
});
