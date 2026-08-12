<?php

namespace App\Http\Controllers\Message;

use App\Http\Controllers\Controller;
use App\Http\Requests\MessageRequest;
use App\Repository\Conversation\ConversationRepositoryInterface;
use App\Repository\Message\MessageRepositoryInterface;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MessageController extends Controller
{
    private MessageRepositoryInterface $messages;

    private ConversationRepositoryInterface $conversations;

    public function __construct(
        MessageRepositoryInterface $messages,
        ConversationRepositoryInterface $conversations
    ) {
        $this->messages = $messages;
        $this->conversations = $conversations;
    }

    public function index(Request $request, string $conversation)
    {
        $this->authorizeParticipant($conversation, $request->user()->id);

        return $this->messages->indexForConversation($conversation);
    }

    public function store(MessageRequest $request, string $conversation)
    {
        $this->authorizeParticipant($conversation, $request->user()->id);

        return $this->messages->store(
            $conversation,
            $request->user()->id,
            $request->validated()['body']
        );
    }

    private function authorizeParticipant(string $conversationId, string $userId): void
    {
        if (! $this->conversations->findForUser($conversationId, $userId)) {
            throw new NotFoundHttpException;
        }
    }
}
