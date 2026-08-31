<?php

namespace Tests\Feature;

use Tests\TestCase;

class PlatformSessionTest extends TestCase
{
    public function test_platform_console_session_requires_a_valid_platform_key(): void
    {
        config()->set('platform.provisioning_key', 'platform-test-key');

        $this->getJson(route('platform.session.show'))
            ->assertUnauthorized();

        $this->withHeader('X-Platform-Provisioning-Key', 'platform-test-key')
            ->getJson(route('platform.session.show'))
            ->assertOk()
            ->assertJsonPath('data.authenticated', true);
    }
}
