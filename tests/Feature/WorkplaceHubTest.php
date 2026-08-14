<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WorkplaceHubTest extends TestCase
{
    use RefreshDatabase;

    public function test_meeting_room_cannot_be_double_booked_and_attendees_can_collaborate(): void
    {
        Storage::fake('local');
        $admin = $this->userWithRole('Admin');
        $attendee = $this->workplaceUser('Attendee');
        $outsider = $this->workplaceUser('Outsider');

        $roomId = $this->actingAs($admin, 'sanctum')->postJson(route('workplace.rooms.store'), [
            'name' => 'Board Room',
            'code' => 'BR-01',
            'capacity' => 12,
            'amenities' => ['Display', 'Whiteboard'],
            'status' => 'active',
        ])->assertCreated()->json('data.id');

        $meetingId = $this->actingAs($admin, 'sanctum')->postJson(route('workplace.meetings.store'), [
            'title' => 'Daily operations review',
            'type' => 'daily_standup',
            'room_id' => $roomId,
            'starts_at' => '2026-08-14T01:00:00.000Z',
            'ends_at' => '2026-08-14T01:30:00.000Z',
            'attendee_ids' => [$attendee->id],
            'links' => [
                ['label' => 'Join video call', 'url' => 'https://meet.example.com/daily-operations'],
                ['label' => 'Working document', 'url' => 'https://docs.example.com/operations'],
            ],
            'recurrence' => 'none',
        ])->assertCreated()->json('data.id');

        $this->actingAs($admin, 'sanctum')->postJson(route('workplace.meetings.store'), [
            'title' => 'Conflicting meeting',
            'type' => 'team_meeting',
            'room_id' => $roomId,
            'starts_at' => '2026-08-14T01:15:00.000Z',
            'ends_at' => '2026-08-14T02:00:00.000Z',
            'recurrence' => 'none',
        ])->assertUnprocessable()->assertJsonValidationErrors('room_id');

        $this->actingAs($attendee, 'sanctum')
            ->getJson(route('workplace.meetings.show', $meetingId))
            ->assertOk()
            ->assertJsonPath('data.title', 'Daily operations review')
            ->assertJsonPath('data.links.0.label', 'Join video call')
            ->assertJsonPath('data.links.1.url', 'https://docs.example.com/operations');

        $this->actingAs($admin, 'sanctum')->postJson(route('workplace.meetings.store'), [
            'title' => 'Unsafe link meeting',
            'type' => 'team_meeting',
            'starts_at' => '2026-08-14T03:00:00.000Z',
            'ends_at' => '2026-08-14T04:00:00.000Z',
            'links' => [['label' => 'Unsafe', 'url' => 'javascript:alert(1)']],
            'recurrence' => 'none',
        ])->assertUnprocessable()->assertJsonValidationErrors('links.0.url');

        $upload = $this->actingAs($attendee, 'sanctum')->post(route('workplace.attachments.store', $meetingId), [
            'file' => UploadedFile::fake()->create('daily-report.pdf', 100, 'application/pdf'),
            'description' => 'Operations report',
        ])->assertCreated();
        $this->assertDatabaseHas('meeting_attachments', [
            'id' => $upload->json('data.id'),
            'meeting_id' => $meetingId,
            'uploaded_by' => $attendee->id,
        ]);

        $this->actingAs($attendee, 'sanctum')->postJson(route('workplace.action-items.store', $meetingId), [
            'title' => 'Send revised forecast',
            'assigned_to' => $attendee->id,
            'priority' => 'high',
            'status' => 'open',
        ])->assertCreated();

        $this->actingAs($attendee, 'sanctum')->postJson(route('workplace.action-items.store', $meetingId), [
            'title' => 'Invalid outsider assignment',
            'assigned_to' => $outsider->id,
            'priority' => 'normal',
            'status' => 'open',
        ])->assertUnprocessable()->assertJsonValidationErrors('assigned_to');

        $this->actingAs($outsider, 'sanctum')
            ->getJson(route('workplace.meetings.show', $meetingId))
            ->assertForbidden();
    }

    public function test_daily_recurrence_creates_individual_meeting_occurrences(): void
    {
        $admin = $this->userWithRole('Admin');

        $this->actingAs($admin, 'sanctum')->postJson(route('workplace.meetings.store'), [
            'title' => 'Daily stand-up',
            'type' => 'daily_standup',
            'starts_at' => '2026-08-17T01:00:00.000Z',
            'ends_at' => '2026-08-17T01:15:00.000Z',
            'recurrence' => 'weekdays',
            'recurrence_until' => '2026-08-19',
        ])->assertCreated()
            ->assertJsonPath('meta.occurrences_created', 3)
            ->assertJsonPath('data.title', 'Daily stand-up');

        $this->assertDatabaseCount('workplace_meetings', 3);
        $this->assertDatabaseMissing('workplace_meetings', ['series_id' => null]);
    }

    public function test_room_availability_and_capacity_are_enforced(): void
    {
        $admin = $this->userWithRole('Admin');
        $attendee = $this->workplaceUser('Capacity attendee');

        $roomId = $this->actingAs($admin, 'sanctum')->postJson(route('workplace.rooms.store'), [
            'name' => 'Focus Room',
            'code' => 'FR-01',
            'capacity' => 1,
            'status' => 'active',
        ])->assertCreated()->json('data.id');

        $meetingId = $this->actingAs($admin, 'sanctum')->postJson(route('workplace.meetings.store'), [
            'title' => 'Private review',
            'type' => 'review',
            'room_id' => $roomId,
            'starts_at' => '2026-08-14T01:00:00.000Z',
            'ends_at' => '2026-08-14T01:30:00.000Z',
            'recurrence' => 'none',
        ])->assertCreated()->json('data.id');

        $this->actingAs($admin, 'sanctum')->getJson(route('workplace.rooms.index', [
            'starts_at' => '2026-08-14T01:15:00.000Z',
            'ends_at' => '2026-08-14T01:45:00.000Z',
        ]))->assertOk()->assertJsonPath('data.0.is_available', false);

        $this->actingAs($admin, 'sanctum')->getJson(route('workplace.rooms.index', [
            'starts_at' => '2026-08-14T01:15:00.000Z',
            'ends_at' => '2026-08-14T01:45:00.000Z',
            'ignore_meeting_id' => $meetingId,
        ]))->assertOk()->assertJsonPath('data.0.is_available', true);

        $this->actingAs($admin, 'sanctum')->postJson(route('workplace.meetings.store'), [
            'title' => 'Over-capacity meeting',
            'type' => 'team_meeting',
            'room_id' => $roomId,
            'starts_at' => '2026-08-14T02:00:00.000Z',
            'ends_at' => '2026-08-14T02:30:00.000Z',
            'attendee_ids' => [$attendee->id],
            'recurrence' => 'none',
        ])->assertUnprocessable()->assertJsonValidationErrors('room_id');
    }

    private function workplaceUser(string $roleName): User
    {
        $role = Role::firstOrCreate(['name' => $roleName]);
        foreach (['view-workplace-hub', 'create-meetings'] as $slug) {
            $permission = Permission::firstOrCreate(
                ['slug' => $slug],
                ['model' => 'Workplace Hub', 'name' => $slug, 'description' => $slug]
            );
            $role->permissions()->syncWithoutDetaching($permission->id);
        }

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function userWithRole(string $roleName): User
    {
        $role = Role::firstOrCreate(['name' => $roleName]);

        return User::factory()->create(['role_id' => $role->id]);
    }
}
