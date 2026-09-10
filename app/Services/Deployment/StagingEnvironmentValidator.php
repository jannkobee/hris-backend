<?php

namespace App\Services\Deployment;

class StagingEnvironmentValidator
{
    /**
     * @param  array<string, string|null>  $values
     * @return array{errors: array<int, string>, warnings: array<int, string>}
     */
    public function validate(array $values): array
    {
        $errors = [];
        $warnings = [];
        $required = [
            'DOMAIN', 'TENANT_BASE_DOMAIN', 'APP_KEY', 'AUDIT_LOG_SIGNING_KEY',
            'PLATFORM_PROVISIONING_KEY', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD',
            'DB_ROOT_PASSWORD', 'MYSQL_IMAGE', 'REDIS_IMAGE', 'MAIL_MAILER',
            'MAIL_HOST', 'MAIL_PORT', 'MAIL_USERNAME', 'MAIL_PASSWORD',
            'MAIL_FROM_ADDRESS', 'REVERB_APP_ID', 'REVERB_APP_KEY',
            'REVERB_APP_SECRET', 'FRONTEND_BIND_ADDRESS', 'FRONTEND_PORT_FORWARD',
            'REVERB_BIND_ADDRESS', 'REVERB_PORT_FORWARD', 'STRIPE_SECRET_KEY',
            'STRIPE_WEBHOOK_SECRET', 'BILLING_PORTAL_RETURN_HOSTS',
        ];

        foreach ($required as $key) {
            $value = $this->value($values, $key);
            if ($value === '') {
                $errors[] = "{$key} is required.";
            } elseif ($this->isPlaceholder($value)) {
                $errors[] = "{$key} still contains a placeholder value.";
            }
        }

        $domain = strtolower($this->value($values, 'DOMAIN'));
        $tenantDomain = strtolower($this->value($values, 'TENANT_BASE_DOMAIN'));
        if ($domain !== '' && ! $this->isHostname($domain)) {
            $errors[] = 'DOMAIN must be a hostname without a scheme, port, or path.';
        }
        if ($tenantDomain !== '' && ! $this->isHostname($tenantDomain)) {
            $errors[] = 'TENANT_BASE_DOMAIN must be a hostname without a scheme, port, or path.';
        }
        if ($domain !== '' && $tenantDomain !== '' && $domain !== $tenantDomain) {
            $warnings[] = 'DOMAIN and TENANT_BASE_DOMAIN differ; verify wildcard tenant DNS and TLS intentionally use the tenant base domain.';
        }

        $appKey = $this->value($values, 'APP_KEY');
        $decodedKey = str_starts_with($appKey, 'base64:')
            ? base64_decode(substr($appKey, 7), true)
            : false;
        if ($appKey !== '' && ($decodedKey === false || strlen($decodedKey) < 32)) {
            $errors[] = 'APP_KEY must be a valid base64-encoded key containing at least 32 bytes.';
        }

        foreach ([
            'AUDIT_LOG_SIGNING_KEY' => 32,
            'PLATFORM_PROVISIONING_KEY' => 32,
            'DB_PASSWORD' => 16,
            'DB_ROOT_PASSWORD' => 16,
            'REVERB_APP_KEY' => 16,
            'REVERB_APP_SECRET' => 32,
        ] as $key => $minimumLength) {
            $value = $this->value($values, $key);
            if ($value !== '' && strlen($value) < $minimumLength) {
                $errors[] = "{$key} must contain at least {$minimumLength} characters.";
            }
        }

        $sensitiveKeys = [
            'APP_KEY', 'AUDIT_LOG_SIGNING_KEY', 'PLATFORM_PROVISIONING_KEY',
            'DB_PASSWORD', 'DB_ROOT_PASSWORD', 'REVERB_APP_SECRET',
        ];
        foreach ($sensitiveKeys as $index => $key) {
            $value = $this->value($values, $key);
            if ($value === '') {
                continue;
            }
            foreach (array_slice($sensitiveKeys, $index + 1) as $otherKey) {
                if (hash_equals($value, $this->value($values, $otherKey))) {
                    $errors[] = "{$key} and {$otherKey} must use different secrets.";
                }
            }
        }

        foreach (['FRONTEND_BIND_ADDRESS', 'REVERB_BIND_ADDRESS'] as $key) {
            if (! in_array($this->value($values, $key), ['127.0.0.1', '::1'], true)) {
                $errors[] = "{$key} must use a loopback address; expose staging only through the TLS proxy.";
            }
        }

        $ports = [];
        foreach (['FRONTEND_PORT_FORWARD', 'REVERB_PORT_FORWARD', 'MAIL_PORT'] as $key) {
            $value = $this->value($values, $key);
            $valid = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]);
            if ($value !== '' && $valid === false) {
                $errors[] = "{$key} must be an integer from 1 through 65535.";
            } elseif ($key !== 'MAIL_PORT' && $valid !== false) {
                $ports[$key] = (int) $valid;
            }
        }
        if (count($ports) === 2 && count(array_unique($ports)) !== 2) {
            $errors[] = 'FRONTEND_PORT_FORWARD and REVERB_PORT_FORWARD must be different.';
        }

        if (($stripeKey = $this->value($values, 'STRIPE_SECRET_KEY')) !== '' && ! str_starts_with($stripeKey, 'sk_test_')) {
            $errors[] = 'STRIPE_SECRET_KEY must be a Stripe test-mode key for staging.';
        }
        if (($webhookSecret = $this->value($values, 'STRIPE_WEBHOOK_SECRET')) !== '' && ! str_starts_with($webhookSecret, 'whsec_')) {
            $errors[] = 'STRIPE_WEBHOOK_SECRET must be a Stripe endpoint signing secret.';
        }

        $portalHosts = array_values(array_filter(array_map(
            static fn (string $host): string => strtolower(trim($host)),
            explode(',', $this->value($values, 'BILLING_PORTAL_RETURN_HOSTS')),
        )));
        if ($domain !== '' && ! in_array($domain, $portalHosts, true)) {
            $errors[] = 'BILLING_PORTAL_RETURN_HOSTS must include DOMAIN.';
        }
        $hasTenantPortalHost = array_filter(
            $portalHosts,
            static fn (string $host): bool => $host !== $tenantDomain && str_ends_with($host, '.'.$tenantDomain),
        ) !== [];
        if ($tenantDomain !== '' && ! $hasTenantPortalHost) {
            $warnings[] = 'BILLING_PORTAL_RETURN_HOSTS has no tenant hostname; add each staging tenant that will open the billing portal.';
        }

        foreach (['MYSQL_IMAGE', 'REDIS_IMAGE'] as $key) {
            if (str_ends_with(strtolower($this->value($values, $key)), ':latest')) {
                $errors[] = "{$key} must use a pinned image tag, not latest.";
            }
        }

        return ['errors' => array_values(array_unique($errors)), 'warnings' => array_values(array_unique($warnings))];
    }

    /** @param  array<string, string|null>  $values */
    private function value(array $values, string $key): string
    {
        return trim((string) ($values[$key] ?? ''));
    }

    private function isPlaceholder(string $value): bool
    {
        return preg_match('/(?:replace[-_ ]?with|replace[-_ ]?me|generate[-_ ]?with|changeme|your[-_ ]?domain|example\.com)/i', $value) === 1;
    }

    private function isHostname(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false
            && ! str_contains($value, '://')
            && ! str_contains($value, '/')
            && ! str_contains($value, ':');
    }
}
