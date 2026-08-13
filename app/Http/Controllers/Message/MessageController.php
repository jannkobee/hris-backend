<?php

namespace App\Http\Controllers\Message;

use App\Http\Controllers\Controller;
use App\Http\Requests\MessageRequest;
use App\Models\MessageAttachment;
use App\Repository\Conversation\ConversationRepositoryInterface;
use App\Repository\Message\MessageRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
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

        $validated = $request->validated();

        return $this->messages->store(
            $conversation,
            $request->user()->id,
            $validated['body'] ?? null,
            $request->file('attachments', [])
        );
    }

    public function attachment(
        Request $request,
        string $conversation,
        MessageAttachment $attachment
    ): StreamedResponse {
        $this->authorizeParticipant($conversation, $request->user()->id);
        if ($attachment->conversation_id !== $conversation) {
            throw new NotFoundHttpException;
        }

        $storage = Storage::disk($attachment->disk);
        if (! $storage->exists($attachment->path)) {
            throw new NotFoundHttpException('Attachment file is unavailable.');
        }

        $headers = ['Content-Type' => $attachment->mime_type ?: 'application/octet-stream'];

        if ($request->boolean('inline') && $attachment->is_image) {
            return $storage->response(
                $attachment->path,
                $attachment->original_name,
                $headers,
                'inline'
            );
        }

        return $storage->download($attachment->path, $attachment->original_name, $headers);
    }

    private function authorizeParticipant(string $conversationId, string $userId): void
    {
        if (! $this->conversations->findForUser($conversationId, $userId)) {
            throw new NotFoundHttpException;
        }
    }
}
