<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_credit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('leave_credit_setting_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('leave_type_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->decimal('credited_amount', 8, 2);
            $table->timestamps();

            // The actual guarantee against double-crediting: even if the
            // command somehow runs twice for the same setting/employee/
            // month (overlapping schedule trigger, manual re-run, etc.),
            // the second insert fails at the DB level rather than silently
            // adding the credit again.
            $table->unique(
                ['leave_credit_setting_id', 'employee_id', 'year', 'month'],
                'leave_credit_logs_unique_run'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_credit_logs');
    }
};
