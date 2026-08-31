<?php

namespace App\Services\Auth;

use App\Models\Organization;
use App\Models\SsoConfiguration;
use App\Models\User;
use App\Services\Plans\PlanEntitlementService;
use App\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OidcService
{
    private TenantContext $tenantContext;

    private AuthServiceInterface $authService;

    private PlanEntitlementService $entitlements;

    public function __construct(TenantContext $tenantContext, AuthServiceInterface $authService, PlanEntitlementService $entitlements)
    {
        $this->tenantContext = $tenantContext;
        $this->authService = $authService;
        $this->entitlements = $entitlements;
    }

    public function authorizationUrl(string $organizationSlug): string
    {
        $organization = $this->organization($organizationSlug);

        return $this->tenantContext->run($organization, function () use ($organization): string {
            $configuration = $this->configuration($organization);
            $discovery = $this->discovery($configuration);
            $state = Str::random(64);
            $nonce = Str::random(64);

            Cache::put($this->stateKey($state), [
                'organization_id' => $organization->id,
                'nonce' => $nonce,
            ], now()->addMinutes((int) config('auth_security.oidc_state_minutes')));

            return $discovery['authorization_endpoint'].'?'.http_build_query([
                'client_id' => $configuration->client_id,
                'response_type' => 'code',
                'redirect_uri' => $this->redirectUri(),
                'scope' => implode(' ', $configuration->scopes ?: ['openid', 'profile', 'email']),
                'state' => $state,
                'nonce' => $nonce,
            ], '', '&', PHP_QUERY_RFC3986);
        });
    }

    public function complete(Request $request): string
    {
        $state = (string) $request->query('state');
        $code = (string) $request->query('code');
        $pending = Cache::pull($this->stateKey($state));

        if (! is_array($pending) || $state === '' || $code === '') {
            throw ValidationException::withMessages(['state' => 'The OIDC login request is invalid or has expired.']);
        }

        $organization = Organization::query()->find($pending['organization_id'] ?? null);
        if (! $organization?->isActive()) {
            throw ValidationException::withMessages(['organization' => 'This organization is not available for SSO login.']);
        }

        return $this->tenantContext->run($organization, function () use ($code, $pending): string {
            $configuration = $this->configuration($this->tenantContext->organization());
            $discovery = $this->discovery($configuration);
            $tokenPayload = Http::asForm()
                ->acceptJson()
                ->timeout(15)
                ->post($discovery['token_endpoint'], [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => $this->redirectUri(),
                    'client_id' => $configuration->client_id,
                    'client_secret' => $configuration->client_secret,
                ])->throw()->json();

            $claims = $this->verifiedClaims((string) ($tokenPayload['id_token'] ?? ''), $configuration, $discovery, (string) $pending['nonce']);
            $email = strtolower((string) ($claims['email'] ?? $claims['preferred_username'] ?? ''));
            $user = $email === '' ? null : User::query()->where('email', $email)->first();

            if (! $user instanceof User) {
                throw ValidationException::withMessages(['email' => 'Your SSO identity is not linked to an HRIS user account.']);
            }

            $result = $this->authService->loginForSso($user);
            $exchangeCode = Str::random(64);
            Cache::put($this->exchangeKey($exchangeCode), $result, now()->addMinutes(2));

            return $exchangeCode;
        });
    }

    public function exchange(string $exchangeCode): array
    {
        $result = Cache::pull($this->exchangeKey($exchangeCode));

        if (! is_array($result)) {
            throw ValidationException::withMessages(['exchange_code' => 'This SSO sign-in has expired or was already used.']);
        }

        return $result;
    }

    private function organization(string $slug): Organization
    {
        $organization = Organization::query()->where('slug', $slug)->first();

        if (! $organization?->isActive()) {
            throw ValidationException::withMessages(['organization' => 'This organization is not available for SSO login.']);
        }

        if (! $this->entitlements->allows($organization, 'sso_scim')) {
            throw ValidationException::withMessages(['sso' => 'SSO is not included in this organization\'s subscription plan.']);
        }

        return $organization;
    }

    private function configuration(Organization $organization): SsoConfiguration
    {
        $configuration = SsoConfiguration::query()->where('is_active', true)->first();

        if (! $configuration instanceof SsoConfiguration) {
            throw ValidationException::withMessages(['sso' => 'SSO is not configured for this organization.']);
        }

        return $configuration;
    }

    private function discovery(SsoConfiguration $configuration): array
    {
        $issuer = rtrim($configuration->issuer_url, '/');
        $discovery = Cache::remember('oidc:discovery:'.sha1($issuer), now()->addHour(), function () use ($issuer): array {
            return Http::acceptJson()->timeout(15)->get($issuer.'/.well-known/openid-configuration')->throw()->json();
        });

        if (($discovery['issuer'] ?? null) !== $issuer || empty($discovery['authorization_endpoint']) || empty($discovery['token_endpoint']) || empty($discovery['jwks_uri'])) {
            throw ValidationException::withMessages(['sso' => 'The configured OIDC discovery document is invalid.']);
        }

        return $discovery;
    }

    private function verifiedClaims(string $idToken, SsoConfiguration $configuration, array $discovery, string $nonce): array
    {
        [$encodedHeader, $encodedClaims, $signature] = explode('.', $idToken, 3) + [null, null, null];
        $header = $this->decode($encodedHeader);
        $claims = $this->decode($encodedClaims);

        if (($header['alg'] ?? null) !== 'RS256' || empty($header['kid']) || ! is_array($claims)) {
            throw ValidationException::withMessages(['sso' => 'The OIDC ID token is invalid.']);
        }

        $keys = Cache::remember('oidc:jwks:'.sha1($discovery['jwks_uri']), now()->addHour(), fn (): array => Http::acceptJson()->timeout(15)->get($discovery['jwks_uri'])->throw()->json('keys', []));
        $key = collect($keys)->firstWhere('kid', $header['kid']);
        $validSignature = is_array($key) && openssl_verify($encodedHeader.'.'.$encodedClaims, $this->base64UrlDecode($signature), $this->publicKey($key), OPENSSL_ALGO_SHA256) === 1;
        $audience = Arr::wrap($claims['aud'] ?? []);

        if (! $validSignature
            || ($claims['iss'] ?? null) !== rtrim($configuration->issuer_url, '/')
            || ! in_array($configuration->client_id, $audience, true)
            || (int) ($claims['exp'] ?? 0) < now()->timestamp
            || ! hash_equals($nonce, (string) ($claims['nonce'] ?? ''))) {
            throw ValidationException::withMessages(['sso' => 'The OIDC ID token could not be validated.']);
        }

        return $claims;
    }

    private function decode(?string $segment): array
    {
        $decoded = json_decode($this->base64UrlDecode((string) $segment), true);

        return is_array($decoded) ? $decoded : [];
    }

    private function publicKey(array $key): string
    {
        $modulus = $this->base64UrlDecode((string) ($key['n'] ?? ''));
        $exponent = $this->base64UrlDecode((string) ($key['e'] ?? ''));
        if ($modulus === '' || $exponent === '') {
            throw ValidationException::withMessages(['sso' => 'The OIDC signing key is invalid.']);
        }

        $rsa = "\x30".$this->derLength(strlen($this->derInteger($modulus).$this->derInteger($exponent))).$this->derInteger($modulus).$this->derInteger($exponent);
        $algorithm = "\x30\x0d\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01\x05\x00";
        $subject = "\x30".$this->derLength(strlen($algorithm."\x03".$this->derLength(strlen($rsa) + 1)."\x00".$rsa)).$algorithm."\x03".$this->derLength(strlen($rsa) + 1)."\x00".$rsa;

        return "-----BEGIN PUBLIC KEY-----\n".chunk_split(base64_encode($subject), 64, "\n")."-----END PUBLIC KEY-----\n";
    }

    private function derInteger(string $value): string
    {
        $value = ltrim($value, "\x00");
        $value = $value === '' ? "\x00" : $value;
        if (ord($value[0]) > 0x7F) {
            $value = "\x00".$value;
        }

        return "\x02".$this->derLength(strlen($value)).$value;
    }

    private function derLength(int $length): string
    {
        return $length < 128 ? chr($length) : chr(0x80 | strlen($bytes = ltrim(pack('N', $length), "\x00"))).$bytes;
    }

    private function base64UrlDecode(string $value): string
    {
        return (string) base64_decode(strtr($value, '-_', '+/').str_repeat('=', (4 - strlen($value) % 4) % 4), true);
    }

    private function redirectUri(): string
    {
        return (string) config('auth_security.oidc_redirect_uri');
    }

    private function stateKey(string $state): string
    {
        return 'oidc:state:'.$state;
    }

    private function exchangeKey(string $code): string
    {
        return 'oidc:exchange:'.$code;
    }
}
