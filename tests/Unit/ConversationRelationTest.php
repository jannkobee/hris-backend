<?php

namespace Tests\Unit;

use App\Models\Conversation;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Tests\TestCase;

class ConversationRelationTest extends TestCase
{
    public function test_it_defines_the_latest_message_relationship(): void
    {
        $this->assertInstanceOf(HasOne::class, (new Conversation())->latestMessage());
    }
}
