<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workplace_meetings', function (Blueprint $table): void {
            $table->json('links')->nullable()->after('decisions');
        });
    }

    public function down(): void
    {
        Schema::table('workplace_meetings', function (Blueprint $table): void {
            $table->dropColumn('links');
        });
    }
};
