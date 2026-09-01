<?php

namespace Tests\Unit;

use App\Http\Middleware\AddSecurityHeaders;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class AddSecurityHeadersTest extends TestCase
{
    public function test_it_adds_browser_security_headers(): void
    {
        $response = (new AddSecurityHeaders)->handle(
            Request::create('/health'),
            fn () => new Response('ok')
        );

        $this->assertStringContainsString("default-src 'self'", $response->headers->get('Content-Security-Policy'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertSame('DENY', $response->headers->get('X-Frame-Options'));
        $this->assertSame('strict-origin-when-cross-origin', $response->headers->get('Referrer-Policy'));
        $this->assertSame('camera=(), microphone=(), geolocation=(self)', $response->headers->get('Permissions-Policy'));
    }
}
