<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_grades', function (Blueprint $table) {
            $table->uuid('id')->primary();

            /**
             * Other columns
             */
            $table->string('name')->unique();
            $table->string('code')->nullable()->unique();
            $table->text('description')->nullable();

            // Optional compensation metadata (remove if not needed now)
            $table->decimal('min_salary', 12, 2)->nullable();
            $table->decimal('max_salary', 12, 2)->nullable();

            $table->timestamps();

            /**
             * Indexes (optional but helpful)
             */
            $table->index('name');
            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_grades');
    }
};
