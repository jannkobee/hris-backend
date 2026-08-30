<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table): void {
            $table->dropUnique('departments_name_unique');
            $table->unique(['organization_id', 'name'], 'departments_organization_name_unique');
        });

        Schema::table('employment_statuses', function (Blueprint $table): void {
            $table->dropUnique('employment_statuses_name_unique');
            $table->unique(['organization_id', 'name'], 'employment_statuses_organization_name_unique');
        });

        Schema::table('job_grades', function (Blueprint $table): void {
            $table->dropUnique('job_grades_name_unique');
            $table->dropUnique('job_grades_code_unique');
            $table->unique(['organization_id', 'name'], 'job_grades_organization_name_unique');
            $table->unique(['organization_id', 'code'], 'job_grades_organization_code_unique');
        });

        Schema::table('employees', function (Blueprint $table): void {
            $table->dropUnique('employees_employee_no_unique');
            $table->unique(['organization_id', 'employee_no'], 'employees_organization_employee_no_unique');
        });

        Schema::table('scheduled_tasks', function (Blueprint $table): void {
            $table->dropUnique('scheduled_tasks_name_unique');
            $table->unique(['organization_id', 'name'], 'scheduled_tasks_organization_name_unique');
        });

        Schema::table('meeting_rooms', function (Blueprint $table): void {
            $table->dropUnique('meeting_rooms_code_unique');
            $table->unique(['organization_id', 'code'], 'meeting_rooms_organization_code_unique');
        });

        Schema::table('holidays', function (Blueprint $table): void {
            $table->dropUnique('holidays_date_unique');
            $table->unique(['organization_id', 'date'], 'holidays_organization_date_unique');
        });

        Schema::table('leave_types', function (Blueprint $table): void {
            $table->unique(['organization_id', 'name'], 'leave_types_organization_name_unique');
        });
    }

    public function down(): void
    {
        Schema::table('leave_types', fn (Blueprint $table) => $table->dropUnique('leave_types_organization_name_unique'));

        Schema::table('holidays', function (Blueprint $table): void {
            $table->dropUnique('holidays_organization_date_unique');
            $table->unique('date');
        });

        Schema::table('meeting_rooms', function (Blueprint $table): void {
            $table->dropUnique('meeting_rooms_organization_code_unique');
            $table->unique('code');
        });

        Schema::table('scheduled_tasks', function (Blueprint $table): void {
            $table->dropUnique('scheduled_tasks_organization_name_unique');
            $table->unique('name');
        });

        Schema::table('employees', function (Blueprint $table): void {
            $table->dropUnique('employees_organization_employee_no_unique');
            $table->unique('employee_no');
        });

        Schema::table('job_grades', function (Blueprint $table): void {
            $table->dropUnique('job_grades_organization_name_unique');
            $table->dropUnique('job_grades_organization_code_unique');
            $table->unique('name');
            $table->unique('code');
        });

        Schema::table('employment_statuses', function (Blueprint $table): void {
            $table->dropUnique('employment_statuses_organization_name_unique');
            $table->unique('name');
        });

        Schema::table('departments', function (Blueprint $table): void {
            $table->dropUnique('departments_organization_name_unique');
            $table->unique('name');
        });
    }
};
