<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ValidateStagingEnvironmentCommandTest extends TestCase
{
    public function test_it_rejects_an_incomplete_file_without_printing_secret_values(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'suitify-hr-staging-env-');
        $secret = 'must-not-appear-in-command-output';
        file_put_contents($path, "APP_KEY={$secret}\nSTRIPE_SECRET_KEY=sk_live_{$secret}\n");

        try {
            $exitCode = Artisan::call('deployment:validate-staging', ['--env-file' => $path]);
            $output = Artisan::output();

            $this->assertSame(1, $exitCode);
            $this->assertStringContainsString('validation failed', $output);
            $this->assertStringNotContainsString($secret, $output);
        } finally {
            @unlink($path);
        }
    }
}
