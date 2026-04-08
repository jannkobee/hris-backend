<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Drop columns that belong to users table
            $table->dropColumn([
                'first_name',
                'middle_name',
                'last_name',
                'suffix',
                'birthdate',
                'gender',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Restore if rollback (same types as your original migration)
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('suffix')->nullable();

            $table->date('birthdate')->nullable();
            $table->string('gender')->nullable();
        });
    }
};
