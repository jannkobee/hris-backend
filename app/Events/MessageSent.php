<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// Implements ShouldBroadcast (not ShouldBroadcastNow) so this rides your
// existing Redis queue worker instead of blocking the request/response cycle.
class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Message $message)
    {
        $this->message->loadMissing(
            'sender:id,first_name,last_name',
            'conversation.participants:id'
        );
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.'.$this->message->conversation_id),
            ...$this->message->conversation->participants
                ->where('id', '!=', $this->message->sender_id)
                ->map(fn ($participant) => new PrivateChannel('App.Models.User.'.$participant->id))
                ->values()
                ->all(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'body' => $this->message->body,
            'created_at' => $this->message->created_at->toIso8601String(),
            'sender' => [
                'id' => $this->message->sender->id,
                'name' => trim($this->message->sender->first_name.' '.$this->message->sender->last_name),
            ],
        ];
    }
}
