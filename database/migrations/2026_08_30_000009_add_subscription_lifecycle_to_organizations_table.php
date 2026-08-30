<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table): void {
            $table->string('subscription_status')->default('active')->after('status');
            $table->timestamp('trial_ends_at')->nullable()->after('subscription_status');
            $table->timestamp('current_period_ends_at')->nullable()->after('trial_ends_at');
            $table->unsignedInteger('employee_limit')->nullable()->after('current_period_ends_at');
            $table->index(['subscription_status', 'current_period_ends_at']);
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table): void {
            $table->dropIndex('organizations_subscription_status_current_period_ends_at_index');
            $table->dropColumn([
                'subscription_status',
                'trial_ends_at',
                'current_period_ends_at',
                'employee_limit',
            ]);
        });
    }
};
