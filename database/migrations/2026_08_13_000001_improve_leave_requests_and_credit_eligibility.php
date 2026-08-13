<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $isSqlite = DB::getDriverName() === 'sqlite';

        Schema::table('leave_requests', function (Blueprint $table) use ($isSqlite) {
            if (! $isSqlite) {
                $table->string('status')->default('pending')->change();
            }
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->index(['employee_id', 'status']);
            $table->index(['start_date', 'end_date']);
        });

        Schema::table('leave_credit_settings', function (Blueprint $table) {
            $table->json('eligible_employment_status_ids')->nullable()->after('run_months');
            $table->json('eligible_department_ids')->nullable()->after('eligible_employment_status_ids');
            $table->json('eligible_position_ids')->nullable()->after('eligible_department_ids');
            $table->json('eligible_job_grade_ids')->nullable()->after('eligible_position_ids');
            $table->unsignedInteger('minimum_service_months')->default(0)->after('eligible_job_grade_ids');
        });
    }

    public function down(): void
    {
        $isSqlite = DB::getDriverName() === 'sqlite';

        Schema::table('leave_credit_settings', function (Blueprint $table) {
            $table->dropColumn([
                'eligible_employment_status_ids',
                'eligible_department_ids',
                'eligible_position_ids',
                'eligible_job_grade_ids',
                'minimum_service_months',
            ]);
        });

        Schema::table('leave_requests', function (Blueprint $table) use ($isSqlite) {
            $table->dropIndex(['employee_id', 'status']);
            $table->dropIndex(['start_date', 'end_date']);
            $table->dropColumn('approved_at');
            if (! $isSqlite) {
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->change();
            }
        });
    }
};
