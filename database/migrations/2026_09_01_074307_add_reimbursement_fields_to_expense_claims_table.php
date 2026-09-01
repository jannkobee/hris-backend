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
        Schema::table('expense_claims', function (Blueprint $table) {
            $table->string('receipt_path')->nullable()->after('description');
            $table->text('reviewer_note')->nullable()->after('reviewed_at');
            $table->uuid('reimbursed_by')->nullable()->after('reviewed_at');
            $table->timestamp('reimbursed_at')->nullable()->after('reimbursed_by');
            $table->string('payment_reference')->nullable()->after('reimbursed_at');
            $table->foreign('reimbursed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expense_claims', function (Blueprint $table) {
            $table->dropForeign(['reimbursed_by']);
            $table->dropColumn(['receipt_path', 'reviewer_note', 'reimbursed_by', 'reimbursed_at', 'payment_reference']);
        });
    }
};
