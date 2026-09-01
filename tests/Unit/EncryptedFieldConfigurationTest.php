<?php

namespace Tests\Unit;

use App\Models\EmployeeDocument;
use App\Models\SsoConfiguration;
use App\Models\User;
use App\Models\WebhookSubscription;
use Tests\TestCase;

class EncryptedFieldConfigurationTest extends TestCase
{
    public function test_sensitive_model_attributes_are_not_kept_as_plaintext(): void
    {
        $document = new EmployeeDocument([
            'document_number' => 'SSS-12-3456789-0',
            'notes' => 'Medical accommodation record',
        ]);
        $user = new User;
        $user->two_factor_secret = 'JBSWY3DPEHPK3PXP';
        $user->two_factor_recovery_codes = ['one-time-code'];
        $sso = new SsoConfiguration(['client_secret' => 'oidc-client-secret']);
        $webhook = new WebhookSubscription(['signing_secret' => 'webhook-signing-secret']);

        $this->assertNotSame('SSS-12-3456789-0', $document->getAttributes()['document_number']);
        $this->assertNotSame('Medical accommodation record', $document->getAttributes()['notes']);
        $this->assertNotSame('JBSWY3DPEHPK3PXP', $user->getAttributes()['two_factor_secret']);
        $this->assertNotSame('oidc-client-secret', $sso->getAttributes()['client_secret']);
        $this->assertNotSame('webhook-signing-secret', $webhook->getAttributes()['signing_secret']);

        $this->assertSame('SSS-12-3456789-0', $document->document_number);
        $this->assertSame('Medical accommodation record', $document->notes);
        $this->assertSame(['one-time-code'], $user->two_factor_recovery_codes);
    }
}
