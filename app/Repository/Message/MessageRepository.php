<?php

namespace App\Repository\Message;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class MessageRepository implements MessageRepositoryInterface
{
    public function indexForConversation(string $conversationId, int $perPage = 30): LengthAwarePaginator
    {
        return Message::where('conversation_id', $conversationId)
            ->with([
                'sender:id,first_name,middle_name,last_name,profile_photo_path,updated_at',
                'attachments',
            ])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /** @param  UploadedFile[]  $attachments */
    public function store(
        string $conversationId,
        string $senderId,
        ?string $body,
        array $attachments = []
    ): Message {
        $storedFiles = [];

        try {
            $message = DB::transaction(function () use (
                $conversationId,
                $senderId,
                $body,
                $attachments,
                &$storedFiles
            ): Message {
                $message = Message::create([
                    'conversation_id' => $conversationId,
                    'sender_id' => $senderId,
                    'body' => trim((string) $body),
                ]);

                foreach ($attachments as $attachment) {
                    $extension = strtolower($attachment->getClientOriginalExtension());
                    $filename = (string) Str::uuid().($extension ? ".{$extension}" : '');
                    $disk = 'local';
                    $path = $attachment->storeAs("messages/{$conversationId}/{$message->id}", $filename, $disk);

                    if (! $path) {
                        throw new \RuntimeException('The message attachment could not be stored.');
                    }

                    $storedFiles[] = [$disk, $path];
                    $message->attachments()->create([
                        'conversation_id' => $conversationId,
                        'disk' => $disk,
                        'path' => $path,
                        'original_name' => $attachment->getClientOriginalName(),
                        'mime_type' => $attachment->getMimeType() ?? 'application/octet-stream',
                        'size' => $attachment->getSize() ?? 0,
                    ]);
                }

                Conversation::whereKey($conversationId)->update(['last_message_at' => now()]);

                // The sender has read the conversation through their new message.
                $message->conversation()->first()
                    ->participants()
                    ->updateExistingPivot($senderId, ['last_read_at' => now()]);

                return $message;
            });
        } catch (Throwable $exception) {
            foreach ($storedFiles as [$disk, $path]) {
                Storage::disk($disk)->delete($path);
            }

            throw $exception;
        }

        broadcast(new MessageSent($message))->toOthers();

        return $message->load([
            'sender:id,first_name,middle_name,last_name,profile_photo_path,updated_at',
            'attachments',
        ]);
    }
}
