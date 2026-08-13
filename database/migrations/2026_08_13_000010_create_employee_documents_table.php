<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_documents', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('employee_id')->constrained()->cascadeOnDelete();
            $table->string('category', 60);
            $table->enum('visibility', ['employee', 'hr_only'])->default('employee');
            $table->string('title');
            $table->string('document_number')->nullable();
            $table->date('issued_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->text('notes')->nullable();
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 150)->nullable();
            $table->unsignedBigInteger('size');
            $table->foreignUuid('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'category']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_documents');
    }
};
