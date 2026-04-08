<?php

use App\Http\Controllers\EmploymentStatus\EmploymentStatusController;
use Illuminate\Support\Facades\Route;

Route::apiResource('/employment-statuses', EmploymentStatusController::class);
