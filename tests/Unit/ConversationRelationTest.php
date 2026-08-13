<?php

namespace Tests\Unit;

use App\Models\Conversation;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Tests\TestCase;

class ConversationRelationTest extends TestCase
{
    public function test_it_defines_the_latest_message_relationship(): void
    {
        $this->assertInstanceOf(HasOne::class, (new Conversation())->latestMessage());
    }

    public function test_it_defines_the_shared_attachments_relationship(): void
    {
        $this->assertInstanceOf(HasManyThrough::class, (new Conversation)->attachments());
    }
}
