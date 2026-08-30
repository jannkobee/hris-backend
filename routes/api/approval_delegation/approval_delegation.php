<?php

use App\Http\Controllers\Approval\ApprovalDelegationController;
use Illuminate\Support\Facades\Route;

Route::apiResource('/approval-delegations', ApprovalDelegationController::class)->except('show');
