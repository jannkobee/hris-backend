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
        Schema::table('overtimes', function (Blueprint $table): void {
            $table->string('day_type', 40)->nullable();
            $table->decimal('premium_multiplier', 5, 2)->default(1);
            $table->decimal('premium_hours', 6, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('overtimes', function (Blueprint $table): void {
            $table->dropColumn(['day_type', 'premium_multiplier', 'premium_hours']);
        });
    }
};
