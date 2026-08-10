<?php

namespace App\Repository\Message;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MessageRepository implements MessageRepositoryInterface
{
    public function indexForConversation(string $conversationId, int $perPage = 30): LengthAwarePaginator
    {
        return Message::where('conversation_id', $conversationId)
            ->with('sender:id,first_name,last_name')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function store(string $conversationId, string $senderId, string $body): Message
    {
        $message = Message::create([
            'conversation_id' => $conversationId,
            'sender_id' => $senderId,
            'body' => $body,
        ]);

        Conversation::whereKey($conversationId)->update(['last_message_at' => now()]);

        // Sending yourself a message counts as having read up to that point,
        // so your own outgoing messages don't show up as "unread" to you.
        $message->conversation()->first()
            ->participants()
            ->updateExistingPivot($senderId, ['last_read_at' => now()]);

        broadcast(new MessageSent($message))->toOthers();

        return $message->load('sender:id,first_name,last_name');
    }
}
