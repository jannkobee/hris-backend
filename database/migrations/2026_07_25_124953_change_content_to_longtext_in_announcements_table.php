<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite treats TEXT and LONGTEXT equivalently. Skipping the no-op
        // change keeps the isolated test database independent of Doctrine DBAL.
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('announcements', function (Blueprint $table) {
            $table->longText('content')->change();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('announcements', function (Blueprint $table) {
            $table->text('content')->change();
        });
    }
};
