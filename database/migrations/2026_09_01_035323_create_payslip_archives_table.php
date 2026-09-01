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
        Schema::create('payslip_archives', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id')->index();
            $table->uuid('payroll_item_id')->index();
            $table->string('disk');
            $table->string('path');
            $table->string('mime_type')->default('application/pdf');
            $table->string('checksum', 64);
            $table->uuid('archived_by');
            $table->timestamp('archived_at');
            $table->timestamps();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('payroll_item_id')->references('id')->on('payroll_items')->cascadeOnDelete();
            $table->foreign('archived_by')->references('id')->on('users');
            $table->unique(['organization_id', 'payroll_item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payslip_archives');
    }
};
