<?php

namespace App\Console\Commands\Concerns;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

trait CollectsOrganizationOwner
{
    private function ownerAttributes(): array
    {
        $email = $this->option('admin-email');
        if (! $email && $this->input->isInteractive()) {
            $email = $this->ask('Administrator email');
        }
        $email = strtolower(trim((string) $email));
        $password = null;
        $variable = $this->option('admin-password-env');
        if ($variable) {
            $password = getenv((string) $variable) ?: null;
        } elseif ($this->input->hasOption('admin-password') && $this->option('admin-password')) {
            $this->warn('--admin-password is deprecated: use the hidden prompt or --admin-password-env to keep passwords out of command history.');
            $password = $this->option('admin-password');
        } elseif ($this->input->isInteractive()) {
            $password = $this->secret('Administrator password (12+ characters, mixed case, number and symbol)');
            $confirmation = $this->secret('Confirm administrator password');
            if (! is_string($password) || ! hash_equals($password, (string) $confirmation)) {
                throw ValidationException::withMessages(['admin_password' => 'Passwords do not match. No administrator was created.']);
            }
        }

        $attributes = ['admin_email' => $email, 'admin_password' => $password];
        Validator::make($attributes, [
            'admin_email' => ['required', 'email', 'max:255'],
            'admin_password' => ['required', Password::min(12)->mixedCase()->numbers()->symbols()],
        ])->validate();

        return $attributes;
    }

    private function reportOwnerValidation(ValidationException $exception): int
    {
        foreach ($exception->errors() as $messages) {
            foreach ($messages as $message) {
                $this->error($message);
            }
        }

        return self::FAILURE;
    }
}
