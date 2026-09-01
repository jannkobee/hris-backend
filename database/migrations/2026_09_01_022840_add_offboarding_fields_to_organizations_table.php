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
        Schema::table('organizations', function (Blueprint $table) {
            $table->timestamp('offboarding_requested_at')->nullable()->after('current_period_ends_at');
            $table->timestamp('offboarding_scheduled_at')->nullable()->after('offboarding_requested_at');
            $table->text('offboarding_reason')->nullable()->after('offboarding_scheduled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['offboarding_requested_at', 'offboarding_scheduled_at', 'offboarding_reason']);
        });
    }
};
