<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_number_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('strategy')->default('yearly_random');
            $table->string('prefix')->default('EMP');
            $table->unsignedInteger('padding')->default(4);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_number_settings');
    }
};
