<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('profile_photo_disk')->nullable()->after('password');
            $table->string('profile_photo_path')->nullable()->after('profile_photo_disk');
            $table->string('profile_photo_name')->nullable()->after('profile_photo_path');
            $table->string('profile_photo_mime', 100)->nullable()->after('profile_photo_name');
            $table->unsignedBigInteger('profile_photo_size')->nullable()->after('profile_photo_mime');
        });

        Schema::table('employees', function (Blueprint $table): void {
            $table->decimal('basic_monthly_salary', 12, 2)->default(0)->after('hire_date');
            $table->string('pay_schedule', 30)->default('semi_monthly')->after('basic_monthly_salary');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            $table->dropColumn(['basic_monthly_salary', 'pay_schedule']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'profile_photo_disk',
                'profile_photo_path',
                'profile_photo_name',
                'profile_photo_mime',
                'profile_photo_size',
            ]);
        });
    }
};
