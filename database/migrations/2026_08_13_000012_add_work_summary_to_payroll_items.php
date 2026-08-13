<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_items', function (Blueprint $table): void {
            $table->decimal('scheduled_days', 6, 2)->default(0)->after('employee_id');
            $table->decimal('days_worked', 6, 2)->default(0)->after('scheduled_days');
            $table->decimal('paid_leave_days', 6, 2)->default(0)->after('days_worked');
            $table->decimal('unpaid_leave_days', 6, 2)->default(0)->after('paid_leave_days');
            $table->decimal('absent_days', 6, 2)->default(0)->after('unpaid_leave_days');
            $table->unsignedInteger('late_minutes')->default(0)->after('absent_days');
            $table->unsignedInteger('undertime_minutes')->default(0)->after('late_minutes');
            $table->decimal('absence_deduction', 12, 2)->default(0)->after('other_earnings');
            $table->decimal('late_undertime_deduction', 12, 2)->default(0)->after('absence_deduction');
            $table->decimal('unpaid_leave_deduction', 12, 2)->default(0)->after('late_undertime_deduction');
            $table->json('exceptions')->nullable()->after('notes');
            $table->timestamp('exceptions_acknowledged_at')->nullable()->after('exceptions');
            $table->foreignUuid('exceptions_acknowledged_by')
                ->nullable()
                ->after('exceptions_acknowledged_at')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payroll_items', function (Blueprint $table): void {
            $table->dropForeign(['exceptions_acknowledged_by']);
            $table->dropColumn([
                'scheduled_days',
                'days_worked',
                'paid_leave_days',
                'unpaid_leave_days',
                'absent_days',
                'late_minutes',
                'undertime_minutes',
                'absence_deduction',
                'late_undertime_deduction',
                'unpaid_leave_deduction',
                'exceptions',
                'exceptions_acknowledged_at',
                'exceptions_acknowledged_by',
            ]);
        });
    }
};
