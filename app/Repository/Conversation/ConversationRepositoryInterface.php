<?php

namespace App\Repository\Conversation;

use App\Models\Conversation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ConversationRepositoryInterface
{
    public function indexForUser(string $userId, int $perPage = 20): LengthAwarePaginator;

    public function findForUser(string $conversationId, string $userId): ?Conversation;

    public function findDirectBetween(string $userId, string $otherUserId): ?Conversation;

    public function create(string $createdBy, Collection $participantIds, ?string $name = null): Conversation;

    public function markRead(string $conversationId, string $userId): void;
}
