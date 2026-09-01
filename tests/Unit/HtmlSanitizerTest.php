<?php

namespace Tests\Unit;

use App\Services\Security\HtmlSanitizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class HtmlSanitizerTest extends TestCase
{
    private HtmlSanitizer $sanitizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sanitizer = new HtmlSanitizer;
    }

    public function test_it_preserves_only_the_supported_rich_text_allowlist(): void
    {
        $result = $this->sanitizer->sanitize(
            '<h1>Notice</h1><p class="lead">Use <strong>care</strong><br><em>today</em>.</p><ul><li>One</li></ul>'
        );

        $this->assertSame(
            'Notice<p>Use <strong>care</strong><br><em>today</em>.</p><ul><li>One</li></ul>',
            $result
        );
    }

    #[DataProvider('unsafeHtml')]
    public function test_it_removes_executable_html(string $html): void
    {
        $result = strtolower($this->sanitizer->sanitize($html));

        $this->assertStringNotContainsString('<script', $result);
        $this->assertStringNotContainsString('javascript:', $result);
        $this->assertStringNotContainsString('onerror', $result);
        $this->assertStringNotContainsString('onclick', $result);
        $this->assertStringNotContainsString('<svg', $result);
    }

    public static function unsafeHtml(): array
    {
        return [
            ['<script>alert(1)</script><p>Safe</p>'],
            ['<img src=x onerror=alert(1)><p onclick="alert(1)">Safe</p>'],
            ['<a href="javascript:alert(1)" onclick="alert(2)">Open</a>'],
            ['<svg><script>alert(1)</script></svg><strong>Safe</strong>'],
            ['<a href="java&#x73;cript:alert(1)">Encoded</a>'],
        ];
    }
}
