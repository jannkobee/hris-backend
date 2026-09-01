<p>Hello,</p>

<p>You have been invited to become the organization owner for <strong>{{ $organization->name }}</strong>.</p>

<p><a href="{{ $acceptanceUrl }}">Accept invitation and create your password</a></p>

<p>This invitation expires on {{ $expiresAt->timezone($organization->timezone)->format('F j, Y g:i A T') }}. If you were not expecting this invitation, you can safely ignore this email.</p>
