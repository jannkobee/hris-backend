<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_periods', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->date('date_from');
            $table->date('date_to');
            $table->date('payout_date');
            $table->enum('frequency', ['monthly', 'semi_monthly'])->default('semi_monthly');
            $table->enum('status', ['draft', 'processed', 'approved', 'paid'])->default('draft');
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->decimal('total_gross', 14, 2)->default(0);
            $table->decimal('total_deductions', 14, 2)->default(0);
            $table->decimal('total_net', 14, 2)->default(0);
            $table->timestamps();

            $table->index(['date_from', 'date_to']);
            $table->index(['status', 'payout_date']);
        });

        Schema::create('payroll_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('payroll_period_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('employee_id')->constrained()->restrictOnDelete();
            $table->decimal('basic_pay', 12, 2)->default(0);
            $table->decimal('overtime_pay', 12, 2)->default(0);
            $table->decimal('allowances', 12, 2)->default(0);
            $table->decimal('other_earnings', 12, 2)->default(0);
            $table->decimal('gross_pay', 12, 2)->default(0);
            $table->decimal('sss_employee', 12, 2)->default(0);
            $table->decimal('sss_employer', 12, 2)->default(0);
            $table->decimal('sss_ec_employer', 12, 2)->default(0);
            $table->decimal('philhealth_employee', 12, 2)->default(0);
            $table->decimal('philhealth_employer', 12, 2)->default(0);
            $table->decimal('pagibig_employee', 12, 2)->default(0);
            $table->decimal('pagibig_employer', 12, 2)->default(0);
            $table->decimal('withholding_tax', 12, 2)->default(0);
            $table->decimal('other_deductions', 12, 2)->default(0);
            $table->decimal('total_deductions', 12, 2)->default(0);
            $table->decimal('net_pay', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->json('calculation_snapshot');
            $table->timestamps();

            $table->unique(['payroll_period_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_items');
        Schema::dropIfExists('payroll_periods');
    }
};
