<?php

use App\Http\Controllers\Reporting\ReportController;
use Illuminate\Support\Facades\Route;

Route::prefix('reports')->name('reports.')->controller(ReportController::class)->group(function (): void {
    Route::post('run', 'run')->name('run');
    Route::post('export', 'export')->name('export');
    Route::apiResource('saved', ReportController::class)
        ->parameters(['saved' => 'savedReport'])
        ->except('show');
});
