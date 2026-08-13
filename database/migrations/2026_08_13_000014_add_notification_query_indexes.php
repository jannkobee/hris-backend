<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversation_participants', function (Blueprint $table): void {
            $table->index(['user_id', 'last_read_at'], 'participants_user_read_index');
        });

        Schema::table('leave_requests', function (Blueprint $table): void {
            $table->index(['status', 'start_date'], 'leave_requests_status_start_index');
        });

        Schema::table('leave_conversion_requests', function (Blueprint $table): void {
            $table->index('status', 'leave_conversions_status_index');
        });

        Schema::table('overtimes', function (Blueprint $table): void {
            $table->index(['status', 'date'], 'overtimes_status_date_index');
        });

        Schema::table('meeting_action_items', function (Blueprint $table): void {
            $table->index(['assigned_to', 'status'], 'meeting_actions_assignee_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('conversation_participants', function (Blueprint $table): void {
            $table->dropIndex('participants_user_read_index');
        });

        Schema::table('leave_requests', function (Blueprint $table): void {
            $table->dropIndex('leave_requests_status_start_index');
        });

        Schema::table('leave_conversion_requests', function (Blueprint $table): void {
            $table->dropIndex('leave_conversions_status_index');
        });

        Schema::table('overtimes', function (Blueprint $table): void {
            $table->dropIndex('overtimes_status_date_index');
        });

        Schema::table('meeting_action_items', function (Blueprint $table): void {
            $table->dropIndex('meeting_actions_assignee_status_index');
        });
    }
};
