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
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('previous_hash', 64)->nullable()->after('route_name');
            $table->string('integrity_hash', 64)->nullable()->after('previous_hash');
            $table->timestamp('retention_until')->nullable()->index()->after('integrity_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['retention_until']);
            $table->dropColumn(['previous_hash', 'integrity_hash', 'retention_until']);
        });
    }
};
