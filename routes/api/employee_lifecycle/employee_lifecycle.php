<?php

use App\Http\Controllers\Employee\EmployeeLifecycleController;
use Illuminate\Support\Facades\Route;

Route::prefix('employee-lifecycle')->name('employee-lifecycle.')->controller(EmployeeLifecycleController::class)->group(function (): void {
    Route::get('checklists', 'index')->name('checklists.index');
    Route::post('checklists', 'storeChecklist')->name('checklists.store');
    Route::post('checklists/{checklist}/tasks', 'storeTask')->name('tasks.store');
    Route::patch('tasks/{task}/complete', 'complete')->name('tasks.complete');
});
