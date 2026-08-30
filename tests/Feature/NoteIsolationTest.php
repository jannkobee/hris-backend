<?php

namespace Tests\Feature;

use App\Models\Note;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NoteIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_personal_notes_are_visible_only_to_their_creator(): void
    {
        $role = Role::firstOrCreate(['name' => 'Admin']);
        $owner = User::factory()->create(['role_id' => $role->id]);
        $otherUser = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($owner);
        $note = Note::create([
            'title' => 'Private reminder',
            'content' => 'Only the owner should see this.',
            'color' => 'primary',
            'is_pinned' => false,
            'is_archived' => false,
        ]);

        $this->assertSame(1, Note::query()->count());
        $this->assertSame($owner->id, $note->created_by);

        $this->actingAs($otherUser);
        $this->assertSame(0, Note::query()->count());
        $this->assertNull(Note::query()->find($note->id));
    }

    public function test_notes_are_created_with_the_current_organization(): void
    {
        $role = Role::firstOrCreate(['name' => 'Admin']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user);
        $note = Note::create([
            'title' => 'Tenant-owned note',
            'color' => 'info',
            'is_pinned' => true,
            'is_archived' => false,
        ]);

        $this->assertSame($user->organization_id, $note->organization_id);
    }
}
