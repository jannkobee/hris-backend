<?php

namespace Tests\Unit;

use App\Services\Deployment\StagingEnvironmentValidator;
use PHPUnit\Framework\TestCase;

class StagingEnvironmentValidatorTest extends TestCase
{
    public function test_it_accepts_an_isolated_staging_configuration(): void
    {
        $result = (new StagingEnvironmentValidator())->validate($this->validConfiguration());

        $this->assertSame([], $result['errors']);
        $this->assertSame([], $result['warnings']);
    }

    public function test_it_rejects_public_bindings_placeholders_reused_secrets_and_live_stripe_keys(): void
    {
        $values = $this->validConfiguration();
        $values['DOMAIN'] = 'hris.example.com';
        $values['FRONTEND_BIND_ADDRESS'] = '0.0.0.0';
        $values['REVERB_PORT_FORWARD'] = $values['FRONTEND_PORT_FORWARD'];
        $values['DB_ROOT_PASSWORD'] = $values['DB_PASSWORD'];
        $values['STRIPE_SECRET_KEY'] = 'sk_live_not_allowed';
        $values['BILLING_PORTAL_RETURN_HOSTS'] = 'tenant.staging.lexisone.test';

        $result = (new StagingEnvironmentValidator())->validate($values);
        $messages = implode(' ', $result['errors']);

        $this->assertStringContainsString('DOMAIN still contains a placeholder', $messages);
        $this->assertStringContainsString('FRONTEND_BIND_ADDRESS must use a loopback', $messages);
        $this->assertStringContainsString('must be different', $messages);
        $this->assertStringContainsString('must use different secrets', $messages);
        $this->assertStringContainsString('test-mode key', $messages);
        $this->assertStringContainsString('must include DOMAIN', $messages);
    }

    /** @return array<string, string> */
    private function validConfiguration(): array
    {
        return [
            'DOMAIN' => 'staging.lexisone.test',
            'TENANT_BASE_DOMAIN' => 'staging.lexisone.test',
            'APP_KEY' => 'base64:'.base64_encode(str_repeat('a', 32)),
            'AUDIT_LOG_SIGNING_KEY' => str_repeat('b', 32),
            'PLATFORM_PROVISIONING_KEY' => str_repeat('c', 32),
            'DB_DATABASE' => 'lexisone_staging',
            'DB_USERNAME' => 'lexisone_staging',
            'DB_PASSWORD' => str_repeat('d', 20),
            'DB_ROOT_PASSWORD' => str_repeat('e', 20),
            'MYSQL_IMAGE' => 'mysql:8.4',
            'REDIS_IMAGE' => 'redis:7-alpine',
            'MAIL_MAILER' => 'smtp',
            'MAIL_HOST' => 'smtp.staging.test',
            'MAIL_PORT' => '587',
            'MAIL_USERNAME' => 'staging-user',
            'MAIL_PASSWORD' => 'staging-mail-password',
            'MAIL_FROM_ADDRESS' => 'no-reply@staging.lexisone.test',
            'REVERB_APP_ID' => 'staging-reverb',
            'REVERB_APP_KEY' => str_repeat('f', 16),
            'REVERB_APP_SECRET' => str_repeat('g', 32),
            'FRONTEND_BIND_ADDRESS' => '127.0.0.1',
            'FRONTEND_PORT_FORWARD' => '18080',
            'REVERB_BIND_ADDRESS' => '127.0.0.1',
            'REVERB_PORT_FORWARD' => '18081',
            'STRIPE_SECRET_KEY' => 'sk_test_staging_fixture',
            'STRIPE_WEBHOOK_SECRET' => 'whsec_staging_fixture',
            'BILLING_PORTAL_RETURN_HOSTS' => 'staging.lexisone.test,acme.staging.lexisone.test',
        ];
    }
}
