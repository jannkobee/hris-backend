<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table): void {
            $table->decimal('time_in_latitude', 10, 7)->nullable()->after('time_in_notes');
            $table->decimal('time_in_longitude', 10, 7)->nullable()->after('time_in_latitude');
            $table->decimal('time_in_accuracy', 10, 2)->nullable()->after('time_in_longitude');
            $table->string('time_in_photo_disk')->nullable()->after('time_in_accuracy');
            $table->string('time_in_photo_path')->nullable()->after('time_in_photo_disk');
            $table->string('time_in_photo_name')->nullable()->after('time_in_photo_path');
            $table->string('time_in_photo_mime', 100)->nullable()->after('time_in_photo_name');
            $table->unsignedBigInteger('time_in_photo_size')->nullable()->after('time_in_photo_mime');

            $table->decimal('time_out_latitude', 10, 7)->nullable()->after('time_out_notes');
            $table->decimal('time_out_longitude', 10, 7)->nullable()->after('time_out_latitude');
            $table->decimal('time_out_accuracy', 10, 2)->nullable()->after('time_out_longitude');
            $table->string('time_out_photo_disk')->nullable()->after('time_out_accuracy');
            $table->string('time_out_photo_path')->nullable()->after('time_out_photo_disk');
            $table->string('time_out_photo_name')->nullable()->after('time_out_photo_path');
            $table->string('time_out_photo_mime', 100)->nullable()->after('time_out_photo_name');
            $table->unsignedBigInteger('time_out_photo_size')->nullable()->after('time_out_photo_mime');
            $table->string('time_out_ip_address')->nullable()->after('ip_address');

            $table->index(['employee_id', 'date'], 'attendances_employee_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table): void {
            $table->dropIndex('attendances_employee_date_index');
            $table->dropColumn([
                'time_in_latitude',
                'time_in_longitude',
                'time_in_accuracy',
                'time_in_photo_disk',
                'time_in_photo_path',
                'time_in_photo_name',
                'time_in_photo_mime',
                'time_in_photo_size',
                'time_out_latitude',
                'time_out_longitude',
                'time_out_accuracy',
                'time_out_photo_disk',
                'time_out_photo_path',
                'time_out_photo_name',
                'time_out_photo_mime',
                'time_out_photo_size',
                'time_out_ip_address',
            ]);
        });
    }
};
