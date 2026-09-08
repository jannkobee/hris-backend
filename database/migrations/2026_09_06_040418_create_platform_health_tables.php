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
        Schema::create('platform_settings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->timestamps();
        });

        Schema::create('platform_health_snapshots', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('status', 32);
            $table->json('checks');
            $table->timestamp('captured_at')->index();
            $table->timestamps();
        });

        Schema::create('platform_operation_logs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('action');
            $table->json('payload')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_operation_logs');
        Schema::dropIfExists('platform_health_snapshots');
        Schema::dropIfExists('platform_settings');
    }
};
