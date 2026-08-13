<?php

namespace Tests\Unit;

use App\Models\Message;
use App\Models\MessageAttachment;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\TestCase;

class MessageAttachmentTest extends TestCase
{
    public function test_message_defines_attachments_relationship(): void
    {
        $this->assertInstanceOf(HasMany::class, (new Message)->attachments());
    }

    public function test_attachment_defines_message_relationship(): void
    {
        $this->assertInstanceOf(BelongsTo::class, (new MessageAttachment)->message());
    }

    public function test_attachment_builds_secure_urls_and_identifies_images(): void
    {
        $attachment = new MessageAttachment([
            'conversation_id' => 'conversation-id',
            'mime_type' => 'image/png',
        ]);
        $attachment->id = 'attachment-id';

        $this->assertTrue($attachment->is_image);
        $this->assertSame(
            '/conversations/conversation-id/attachments/attachment-id',
            $attachment->download_url
        );
        $this->assertSame(
            '/conversations/conversation-id/attachments/attachment-id?inline=1',
            $attachment->preview_url
        );
    }
}
