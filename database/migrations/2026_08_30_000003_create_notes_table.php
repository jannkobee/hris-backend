<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignUuid('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->longText('content')->nullable();
            $table->string('category', 80)->nullable();
            $table->string('color', 20)->default('primary');
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_archived')->default(false);
            $table->timestamps();

            $table->index(['organization_id', 'created_by', 'is_archived']);
            $table->index(['organization_id', 'created_by', 'is_pinned']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
