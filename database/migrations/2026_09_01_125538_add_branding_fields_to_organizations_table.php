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
            $table->string('brand_logo_disk')->nullable()->after('name');
            $table->string('brand_logo_path')->nullable()->after('brand_logo_disk');
            $table->string('brand_logo_name')->nullable()->after('brand_logo_path');
            $table->string('brand_logo_mime', 100)->nullable()->after('brand_logo_name');
            $table->unsignedBigInteger('brand_logo_size')->nullable()->after('brand_logo_mime');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table): void {
            $table->dropColumn([
                'brand_logo_disk',
                'brand_logo_path',
                'brand_logo_name',
                'brand_logo_mime',
                'brand_logo_size',
            ]);
        });
    }
};
