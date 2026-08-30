<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class MfaService
{
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public function generateSecret(): string
    {
        $secret = '';

        for ($index = 0; $index < 32; $index++) {
            $secret .= self::BASE32_ALPHABET[random_int(0, strlen(self::BASE32_ALPHABET) - 1)];
        }

        return $secret;
    }

    public function verify(User $user, string $code): bool
    {
        if (! $user->two_factor_secret) {
            return false;
        }

        if ($this->verifyTotp($user->two_factor_secret, $code)) {
            return true;
        }

        return $this->consumeRecoveryCode($user, $code);
    }

    public function verifyTotp(string $secret, string $code): bool
    {
        $code = preg_replace('/\s+/', '', $code) ?? '';

        if (! preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $counter = intdiv(time(), 30);

        for ($offset = -1; $offset <= 1; $offset++) {
            if (hash_equals($this->totp($secret, $counter + $offset), $code)) {
                return true;
            }
        }

        return false;
    }

    public function recoveryCodes(): array
    {
        return collect(range(1, 8))
            ->map(fn (): string => strtoupper(bin2hex(random_bytes(5))))
            ->all();
    }

    public function hashedRecoveryCodes(array $codes): array
    {
        return array_map(fn (string $code): string => Hash::make($code), $codes);
    }

    public function otpauthUrl(User $user, string $secret): string
    {
        $issuer = (string) config('auth_security.mfa_issuer');
        $label = rawurlencode($issuer.':'.$user->email);

        return 'otpauth://totp/'.$label.'?secret='.$secret.'&issuer='.rawurlencode($issuer).'&algorithm=SHA1&digits=6&period=30';
    }

    private function consumeRecoveryCode(User $user, string $code): bool
    {
        $normalized = strtoupper(str_replace([' ', '-'], '', $code));
        $recoveryCodes = (array) ($user->two_factor_recovery_codes ?? []);

        foreach ($recoveryCodes as $index => $hash) {
            if (! Hash::check($normalized, $hash)) {
                continue;
            }

            unset($recoveryCodes[$index]);
            $user->forceFill(['two_factor_recovery_codes' => array_values($recoveryCodes)])->save();

            return true;
        }

        return false;
    }

    private function totp(string $secret, int $counter): string
    {
        $hash = hash_hmac('sha1', pack('N*', 0, $counter), $this->decodeBase32($secret), true);
        $offset = ord($hash[19]) & 0x0F;
        $binary = ((ord($hash[$offset]) & 0x7F) << 24)
            | (ord($hash[$offset + 1]) << 16)
            | (ord($hash[$offset + 2]) << 8)
            | ord($hash[$offset + 3]);

        return str_pad((string) ($binary % 1_000_000), 6, '0', STR_PAD_LEFT);
    }

    private function decodeBase32(string $secret): string
    {
        $secret = strtoupper(preg_replace('/[^A-Z2-7]/', '', $secret) ?? '');
        $bits = '';

        foreach (str_split($secret) as $character) {
            $bits .= str_pad(decbin(strpos(self::BASE32_ALPHABET, $character)), 5, '0', STR_PAD_LEFT);
        }

        $bytes = '';
        foreach (str_split(substr($bits, 0, intdiv(strlen($bits), 8) * 8), 8) as $byte) {
            $bytes .= chr(bindec($byte));
        }

        return $bytes;
    }
}
