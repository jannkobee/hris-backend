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
        Schema::create('statutory_rules', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('country_code', 2)->default('PH');
            $table->date('effective_from');
            $table->date('effective_until')->nullable();
            $table->json('rules');
            $table->timestamps();

            $table->index(['organization_id', 'country_code', 'effective_from'], 'stat_rules_org_country_effective_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('statutory_rules');
    }
};
