<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('name')->unique();
            $table->text('description')->nullable();

            // The artisan command signature to run, e.g. "reminders:send" or
            // "reminders:send --type=weekly".
            $table->string('command');

            // daily | weekly | monthly | yearly | custom
            $table->string('frequency');

            // HH:MM, used by every frequency except "custom".
            $table->time('run_time')->nullable();

            // Weekly only: array of ISO week days, 0 (Sunday) - 6 (Saturday).
            $table->json('run_days')->nullable();

            // Monthly / yearly: day of month the task should run on.
            $table->unsignedTinyInteger('run_day_of_month')->nullable();

            // Yearly only: array of months, 1 (January) - 12 (December).
            $table->json('run_months')->nullable();

            // Only used when frequency = "custom" — a raw 5-field cron expression.
            $table->string('cron_expression')->nullable();

            $table->string('timezone')->default(config('app.timezone'));
            $table->boolean('is_active')->default(true);

            $table->timestamp('last_run_at')->nullable();
            $table->text('last_run_output')->nullable();
            $table->timestamp('next_run_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_tasks');
    }
};
