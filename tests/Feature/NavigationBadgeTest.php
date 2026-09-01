<?php

namespace Tests\Feature;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NavigationBadgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_unread_messages_without_exposing_unauthorized_module_counts(): void
    {
        $user = User::factory()->create();
        $sender = User::factory()->create();
        $conversation = Conversation::create([
            'created_by' => $sender->id,
            'is_group' => false,
        ]);
        $conversation->participants()->attach([
            $user->id => ['id' => (string) Str::uuid()],
            $sender->id => ['id' => (string) Str::uuid()],
        ]);
        $incomingMessage = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'body' => 'Unread for recipient',
        ]);
        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'body' => 'Own messages are never unread',
        ]);

        $channelNames = collect((new MessageSent($incomingMessage))->broadcastOn())
            ->map(fn ($channel) => $channel->name);
        $this->assertTrue($channelNames->contains('private-conversation.'.$conversation->id));
        $this->assertTrue($channelNames->contains('private-App.Models.User.'.$user->id));
        $this->assertFalse($channelNames->contains('private-App.Models.User.'.$sender->id));

        $response = $this->actingAs($user, 'sanctum')
            ->getJson(route('navigation.badges'))
            ->assertOk()
            ->assertJsonPath('data.messages', 1);

        $this->assertSame(['messages', 'notifications'], array_keys($response->json('data')));

        $conversation->participants()->updateExistingPivot($user->id, ['last_read_at' => now()->addSecond()]);
        $this->actingAs($user, 'sanctum')
            ->getJson(route('navigation.badges'))
            ->assertOk()
            ->assertJsonPath('data.messages', 0);
    }
}
