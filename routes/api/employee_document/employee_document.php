<?php

use App\Http\Controllers\Employee\EmployeeDocumentController;
use Illuminate\Support\Facades\Route;

Route::middleware('plan:employee_documents')->group(function (): void {
    Route::controller(EmployeeDocumentController::class)->group(function (): void {
        Route::get('employee-documents/categories', 'categories')->name('employee-documents.categories');
        Route::get('employees/{employee}/documents', 'index')->name('employees.documents.index');
        Route::post('employees/{employee}/documents', 'store')->name('employees.documents.store');
        Route::put('employee-documents/{document}', 'update')->name('employee-documents.update');
        Route::delete('employee-documents/{document}', 'destroy')->name('employee-documents.destroy');
        Route::get('employee-documents/{document}/download', 'download')->name('employee-documents.download');
    });
});
