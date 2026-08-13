<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_rooms', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name', 120);
            $table->string('code', 40)->nullable()->unique();
            $table->string('location')->nullable();
            $table->string('floor', 80)->nullable();
            $table->unsignedSmallInteger('capacity')->default(1);
            $table->json('amenities')->nullable();
            $table->text('description')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('workplace_meetings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('series_id')->nullable()->index();
            $table->foreignUuid('room_id')->nullable()->constrained('meeting_rooms')->nullOnDelete();
            $table->foreignUuid('organizer_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 180);
            $table->string('type', 40)->default('meeting')->index();
            $table->text('agenda')->nullable();
            $table->longText('minutes')->nullable();
            $table->json('decisions')->nullable();
            $table->dateTime('starts_at')->index();
            $table->dateTime('ends_at')->index();
            $table->string('status', 20)->default('scheduled')->index();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['room_id', 'starts_at', 'ends_at'], 'meetings_room_schedule_index');
        });

        Schema::create('meeting_attendees', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('meeting_id')->constrained('workplace_meetings')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_required')->default(true);
            $table->string('response', 20)->default('pending');
            $table->timestamps();

            $table->unique(['meeting_id', 'user_id']);
        });

        Schema::create('meeting_attachments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('meeting_id')->constrained('workplace_meetings')->cascadeOnDelete();
            $table->foreignUuid('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('disk', 40)->default('local');
            $table->string('path', 500);
            $table->string('original_name');
            $table->string('mime_type', 150)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('description', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('meeting_action_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('meeting_id')->constrained('workplace_meetings')->cascadeOnDelete();
            $table->foreignUuid('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 220);
            $table->text('description')->nullable();
            $table->string('priority', 20)->default('normal');
            $table->string('status', 20)->default('open')->index();
            $table->dateTime('due_at')->nullable()->index();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_action_items');
        Schema::dropIfExists('meeting_attachments');
        Schema::dropIfExists('meeting_attendees');
        Schema::dropIfExists('workplace_meetings');
        Schema::dropIfExists('meeting_rooms');
    }
};
