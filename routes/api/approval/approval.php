<?php

use App\Http\Controllers\Approval\ApprovalInboxController;
use Illuminate\Support\Facades\Route;

Route::get('/approvals/inbox', [ApprovalInboxController::class, 'index'])->name('approvals.inbox');
