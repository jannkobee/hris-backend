<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_credit_settings', function (Blueprint $table): void {
            $table->boolean('allow_negative_balance')->default(false);
            $table->decimal('negative_balance_limit', 5, 2)->default(0);
            $table->decimal('carry_over_limit', 5, 2)->default(0);
            $table->unsignedTinyInteger('carry_over_expiry_month')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('leave_credit_settings', function (Blueprint $table): void {
            $table->dropColumn(['allow_negative_balance', 'negative_balance_limit', 'carry_over_limit', 'carry_over_expiry_month']);
        });
    }
};
