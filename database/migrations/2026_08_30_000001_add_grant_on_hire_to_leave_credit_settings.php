<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_credit_settings', function (Blueprint $table): void {
            $table->boolean('grant_on_hire')
                ->default(false)
                ->after('minimum_service_months');
        });
    }

    public function down(): void
    {
        Schema::table('leave_credit_settings', function (Blueprint $table): void {
            $table->dropColumn('grant_on_hire');
        });
    }
};
