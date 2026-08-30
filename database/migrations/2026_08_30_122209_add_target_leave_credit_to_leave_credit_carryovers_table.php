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
        Schema::table('leave_credit_carryovers', function (Blueprint $table) {
            Schema::table('leave_credit_carryovers', function (Blueprint $table): void {
                $table->foreignUuid('target_leave_credit_id')->nullable()->after('leave_credit_id')->constrained('leave_credits')->nullOnDelete();
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_credit_carryovers', function (Blueprint $table) {
            Schema::table('leave_credit_carryovers', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('target_leave_credit_id');
            });
        });
    }
};
