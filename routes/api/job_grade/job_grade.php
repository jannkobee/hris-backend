<?php

use App\Http\Controllers\JobGrade\JobGradeController;
use Illuminate\Support\Facades\Route;

Route::apiResource('/job-grades', JobGradeController::class);
