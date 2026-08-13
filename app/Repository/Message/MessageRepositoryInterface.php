<?php

namespace App\Repository\Message;

use App\Models\Message;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface MessageRepositoryInterface
{
    public function indexForConversation(string $conversationId, int $perPage = 30): LengthAwarePaginator;

    public function store(string $conversationId, string $senderId, ?string $body, array $attachments = []): Message;
}
