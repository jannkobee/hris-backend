<p>Hello {{ $user->full_name ?: $user->email }},</p>

<p>We received a request to reset the password for your HRIS account.</p>

<p><a href="{{ $resetUrl }}">Reset your password</a></p>

<p>This link expires in {{ $expiresInMinutes }} minutes. If you did not request a reset, you can safely ignore this email.</p>
