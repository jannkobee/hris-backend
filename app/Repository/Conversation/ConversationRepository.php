<?php

namespace App\Repository\Conversation;

use App\Models\Conversation;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// NOTE: written as a standalone class implementing the interface directly.
// If your other repositories extend a shared BaseRepository, adjust the
// constructor/imports to match — the method bodies don't depend on it.
class ConversationRepository implements ConversationRepositoryInterface
{
    public function indexForUser(string $userId, int $perPage = 20): LengthAwarePaginator
    {
        return Conversation::query()
            ->whereHas('participants', fn ($q) => $q->whereKey($userId))
            ->with([
                'participants:id,role_id,first_name,middle_name,last_name,email,profile_photo_path,updated_at',
                'participants.role:id,name',
                'latestMessage.sender:id,first_name,middle_name,last_name,profile_photo_path,updated_at',
                'latestMessage.attachments',
            ])
            ->withCount(['messages as unread_count' => function ($q) use ($userId) {
                $q->whereHas('conversation.participants', function ($p) use ($userId) {
                    $p->whereKey($userId);
                })->where(function ($q) use ($userId) {
                    $q->whereRaw(
                        'messages.created_at > (select last_read_at from conversation_participants
                            where conversation_participants.conversation_id = messages.conversation_id
                            and conversation_participants.user_id = ?)',
                        [$userId]
                    )->orWhereRaw(
                        '(select last_read_at from conversation_participants
                            where conversation_participants.conversation_id = messages.conversation_id
                            and conversation_participants.user_id = ?) is null',
                        [$userId]
                    );
                });
            }])
            ->orderByDesc('last_message_at')
            ->paginate($perPage);
    }

    public function findForUser(string $conversationId, string $userId): ?Conversation
    {
        return Conversation::whereKey($conversationId)
            ->whereHas('participants', fn ($q) => $q->whereKey($userId))
            ->with([
                'participants:id,role_id,first_name,middle_name,last_name,email,profile_photo_path,updated_at',
                'participants.role:id,name',
            ])
            ->first();
    }

    public function findDirectBetween(string $userId, string $otherUserId): ?Conversation
    {
        return Conversation::where('is_group', false)
            ->whereHas('participants', fn ($q) => $q->whereKey($userId))
            ->whereHas('participants', fn ($q) => $q->whereKey($otherUserId))
            ->first();
    }

    public function recipientsForUser(string $userId): Collection
    {
        return $this->recipientQuery($userId)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get([
                'id',
                'role_id',
                'first_name',
                'middle_name',
                'last_name',
                'profile_photo_path',
                'updated_at',
            ]);
    }

    public function canMessageUser(string $senderId, string $recipientId): bool
    {
        return $this->recipientQuery($senderId)
            ->whereKey($recipientId)
            ->exists();
    }

    public function create(string $createdBy, Collection $participantIds, string $name = null): Conversation
    {
        return DB::transaction(function () use ($createdBy, $participantIds, $name) {
            $allParticipants = $participantIds->push($createdBy)->unique();

            $conversation = Conversation::create([
                'name' => $name,
                'is_group' => $allParticipants->count() > 2,
                'created_by' => $createdBy,
            ]);

            $conversation->participants()->attach(
                $allParticipants
                    ->mapWithKeys(fn (string $userId) => [$userId => [
                        'id' => (string) Str::uuid(),
                        'organization_id' => app(TenantContext::class)->id(),
                    ]])
                    ->all()
            );

            return $conversation->load([
                'participants:id,role_id,first_name,middle_name,last_name,email,profile_photo_path,updated_at',
                'participants.role:id,name',
            ]);
        });
    }

    public function markRead(string $conversationId, string $userId): void
    {
        DB::table('conversation_participants')
            ->where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->where('organization_id', app(TenantContext::class)->id())
            ->update(['last_read_at' => now()]);
    }

    private function recipientQuery(string $userId)
    {
        $query = User::query()
            ->with('role:id,name')
            ->whereKeyNot($userId);

        if (! $this->isAdministrator($userId)) {
            $query->whereHas('role', function ($role) {
                $role->whereIn('name', config('messaging.administrator_roles'));
            });
        }

        return $query;
    }

    private function isAdministrator(string $userId): bool
    {
        return User::whereKey($userId)
            ->whereHas('role', function ($role) {
                $role->whereIn('name', config('messaging.administrator_roles'));
            })
            ->exists();
    }
}
