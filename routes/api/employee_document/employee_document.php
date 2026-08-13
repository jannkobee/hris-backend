<?php

use App\Http\Controllers\Employee\EmployeeDocumentController;
use Illuminate\Support\Facades\Route;

Route::get('employee-documents/categories', [EmployeeDocumentController::class, 'categories'])->name('employee-documents.categories');
Route::get('employees/{employee}/documents', [EmployeeDocumentController::class, 'index'])->name('employees.documents.index');
Route::post('employees/{employee}/documents', [EmployeeDocumentController::class, 'store'])->name('employees.documents.store');
Route::put('employee-documents/{document}', [EmployeeDocumentController::class, 'update'])->name('employee-documents.update');
Route::delete('employee-documents/{document}', [EmployeeDocumentController::class, 'destroy'])->name('employee-documents.destroy');
Route::get('employee-documents/{document}/download', [EmployeeDocumentController::class, 'download'])->name('employee-documents.download');
