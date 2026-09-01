<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('training_courses', fn (Blueprint $table) => $table->foreign('organization_id', 'training_course_org_fk')->references('id')->on('organizations')->cascadeOnDelete());
        Schema::table('training_enrollments', function (Blueprint $table): void {
            $table->foreign('organization_id', 'training_enrollment_org_fk')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('course_id', 'training_enrollment_course_fk')->references('id')->on('training_courses')->cascadeOnDelete();
            $table->foreign('employee_id', 'training_enrollment_employee_fk')->references('id')->on('employees')->cascadeOnDelete();
        });
        Schema::table('benefit_plans', fn (Blueprint $table) => $table->foreign('organization_id', 'benefit_plan_org_fk')->references('id')->on('organizations')->cascadeOnDelete());
        Schema::table('benefit_enrollments', function (Blueprint $table): void {
            $table->foreign('organization_id', 'benefit_enrollment_org_fk')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('benefit_plan_id', 'benefit_enrollment_plan_fk')->references('id')->on('benefit_plans')->cascadeOnDelete();
            $table->foreign('employee_id', 'benefit_enrollment_employee_fk')->references('id')->on('employees')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('benefit_enrollments', function (Blueprint $table): void {
            $table->dropForeign('benefit_enrollment_org_fk');
            $table->dropForeign('benefit_enrollment_plan_fk');
            $table->dropForeign('benefit_enrollment_employee_fk');
        });
        Schema::table('benefit_plans', fn (Blueprint $table) => $table->dropForeign('benefit_plan_org_fk'));
        Schema::table('training_enrollments', function (Blueprint $table): void {
            $table->dropForeign('training_enrollment_org_fk');
            $table->dropForeign('training_enrollment_course_fk');
            $table->dropForeign('training_enrollment_employee_fk');
        });
        Schema::table('training_courses', fn (Blueprint $table) => $table->dropForeign('training_course_org_fk'));
    }
};
