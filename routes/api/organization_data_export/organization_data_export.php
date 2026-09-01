<?php

use App\Http\Controllers\OrganizationDataExportController;
use Illuminate\Support\Facades\Route;

Route::prefix('organization-data-exports')->name('organization-data-exports.')->controller(OrganizationDataExportController::class)->group(function (): void {
    Route::get('/', 'index')->name('index');
    Route::post('/', 'store')->name('store');
    Route::get('/{organizationDataExport}/download', 'download')->name('download');
});
