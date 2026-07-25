<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_conversion_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignUuid('leave_type_id')->constrained('leave_types')->cascadeOnDelete();
            $table->foreignUuid('leave_credit_id')->nullable()->constrained('leave_credits')->nullOnDelete();

            $table->decimal('credits_requested', 5, 2);
            $table->decimal('monetary_value', 10, 2)->nullable();

            $table->text('reason')->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected, cancelled

            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('remarks')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_conversion_requests');
    }
};
