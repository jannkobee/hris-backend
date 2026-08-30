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
        Schema::table('saved_reports', function (Blueprint $table): void {
            $table->string('delivery_frequency', 20)->nullable()->after('filters');
            $table->unsignedSmallInteger('delivery_period_days')->default(30)->after('delivery_frequency');
            $table->json('delivery_recipients')->nullable()->after('delivery_period_days');
            $table->timestamp('next_delivery_at')->nullable()->after('delivery_recipients');
            $table->timestamp('last_delivered_at')->nullable()->after('next_delivery_at');
            $table->text('last_delivery_error')->nullable()->after('last_delivered_at');
            $table->index(['organization_id', 'next_delivery_at'], 'saved_reports_delivery_due_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('saved_reports', function (Blueprint $table): void {
            $table->dropIndex('saved_reports_delivery_due_idx');
            $table->dropColumn([
                'delivery_frequency', 'delivery_period_days', 'delivery_recipients',
                'next_delivery_at', 'last_delivered_at', 'last_delivery_error',
            ]);
        });
    }
};
