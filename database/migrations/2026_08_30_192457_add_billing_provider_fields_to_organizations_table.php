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
        Schema::table('organizations', function (Blueprint $table): void {
            $table->string('billing_provider')->nullable()->after('employee_limit');
            $table->string('billing_customer_id')->nullable()->after('billing_provider');
            $table->string('billing_subscription_id')->nullable()->after('billing_customer_id');
            $table->string('billing_interval')->nullable()->after('billing_subscription_id');
            $table->unique(['billing_provider', 'billing_subscription_id'], 'organizations_billing_subscription_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table): void {
            $table->dropUnique('organizations_billing_subscription_unique');
            $table->dropColumn(['billing_provider', 'billing_customer_id', 'billing_subscription_id', 'billing_interval']);
        });
    }
};
