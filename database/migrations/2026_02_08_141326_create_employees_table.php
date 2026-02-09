<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->uuid('id')->primary();

            /**
             * Reference columns first
             */
            $table->uuid('user_id')->nullable();
            $table->uuid('employment_status_id')->nullable();
            $table->uuid('department_id')->nullable();
            $table->uuid('position_id')->nullable();
            $table->uuid('job_grade_id')->nullable();

            /**
             * Other columns
             */
            $table->string('employee_no')->unique();

            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('suffix')->nullable();

            $table->date('birthdate')->nullable();
            $table->string('gender')->nullable();
            $table->date('hire_date')->nullable();

            $table->json('meta')->nullable();

            $table->timestamps();

            /**
             * Indexes
             */
            $table->index('user_id');
            $table->index('employment_status_id');
            $table->index('department_id');
            $table->index('position_id');
            $table->index('job_grade_id');

            /**
             * Foreign keys
             */
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('employment_status_id')
                ->references('id')
                ->on('employment_statuses')
                ->nullOnDelete();

            $table->foreign('department_id')
                ->references('id')
                ->on('departments')
                ->nullOnDelete();

            $table->foreign('position_id')
                ->references('id')
                ->on('positions')
                ->nullOnDelete();

            $table->foreign('job_grade_id')
                ->references('id')
                ->on('job_grades')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
