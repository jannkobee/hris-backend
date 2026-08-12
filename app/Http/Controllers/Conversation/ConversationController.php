<?php

namespace App\Http\Controllers\Conversation;

use App\Http\Controllers\Controller;
use App\Repository\Conversation\ConversationRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ConversationController extends Controller
{
    private ConversationRepositoryInterface $conversations;

    public function __construct(ConversationRepositoryInterface $conversations)
    {
        $this->conversations = $conversations;
    }

    public function index(Request $request)
    {
        return $this->conversations->indexForUser($request->user()->id);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'participant_ids' => ['required', 'array', 'min:1'],
            'participant_ids.*' => ['uuid', 'distinct', 'exists:users,id'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $participantIds = Collection::make($validated['participant_ids']);

        $unauthorizedRecipient = $participantIds->first(
            fn (string $participantId) => ! $this->conversations->canMessageUser(
                $request->user()->id,
                $participantId
            )
        );

        if ($unauthorizedRecipient !== null) {
            abort(403, 'You are not allowed to start a conversation with this user.');
        }

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

    public function recipients(Request $request)
    {
        return $this->conversations->recipientsForUser($request->user()->id);
    }

    public function markRead(Request $request, string $conversation)
    {
        if ($this->conversations->findForUser($conversation, $request->user()->id) === null) {
            abort(404);
        }

        $this->conversations->markRead($conversation, $request->user()->id);

        return response()->noContent();
    }
}
