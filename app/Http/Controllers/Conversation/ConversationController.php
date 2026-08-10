<?php

namespace App\Http\Controllers\Conversation;

use App\Http\Controllers\Controller;
use App\Repository\Conversation\ConversationRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ConversationController extends Controller
{
    public function __construct(
        protected ConversationRepositoryInterface $conversations
    ) {}

    public function index(Request $request)
    {
        return $this->conversations->indexForUser($request->user()->id);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'participant_ids' => ['required', 'array', 'min:1'],
            'participant_ids.*' => ['uuid', 'exists:users,id'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $participantIds = Collection::make($validated['participant_ids']);

        // Reuse the existing 1:1 thread instead of spawning a duplicate
        // every time two users start a "new" conversation with each other.
        if ($participantIds->count() === 1) {
            $existing = $this->conversations->findDirectBetween(
                $request->user()->id,
                $participantIds->first()
            );

            if ($existing) {
                return $existing;
            }
        }

        return $this->conversations->create(
            $request->user()->id,
            $participantIds,
            $validated['name'] ?? null
        );
    }

    public function markRead(Request $request, string $conversation)
    {
        $this->conversations->markRead($conversation, $request->user()->id);

        return response()->noContent();
    }
}
