<?php

use App\Http\Controllers\Approval\ApprovalInboxController;
use App\Http\Controllers\Approval\ApprovalWorkflowController;
use Illuminate\Support\Facades\Route;

Route::get('/approvals/inbox', [ApprovalInboxController::class, 'index'])->name('approvals.inbox');

Route::prefix('approval-workflows')
    ->name('approval-workflows.')
    ->controller(ApprovalWorkflowController::class)
    ->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::post('{workflow}/steps', 'storeStep')->name('steps.store');
    });
