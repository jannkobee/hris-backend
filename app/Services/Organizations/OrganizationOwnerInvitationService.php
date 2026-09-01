<?php

namespace App\Services\Organizations;

use App\Mail\OrganizationOwnerInvitationMail;
use App\Models\Organization;
use App\Models\OrganizationOwnerInvitation;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLog\AuditLogServiceInterface;
use App\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrganizationOwnerInvitationService
{
    private TenantContext $tenantContext;

    private AuditLogServiceInterface $auditLogs;

    public function __construct(TenantContext $tenantContext, AuditLogServiceInterface $auditLogs)
    {
        $this->tenantContext = $tenantContext;
        $this->auditLogs = $auditLogs;
    }

    public function invite(Organization $organization, array $attributes): array
    {
        return $this->tenantContext->run($organization, function () use ($organization, $attributes): array {
            $email = Str::lower(trim((string) $attributes['email']));
            $token = Str::random(64);
            $expiresAt = now()->addDays((int) ($attributes['expires_in_days'] ?? config('platform.owner_invitation_days', 7)));

            OrganizationOwnerInvitation::query()
                ->where('email', $email)
                ->whereNull('accepted_at')
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);

            $invitation = OrganizationOwnerInvitation::create([
                'email' => $email,
                'first_name' => $attributes['first_name'] ?? null,
                'last_name' => $attributes['last_name'] ?? null,
                'token_hash' => hash('sha256', $token),
                'expires_at' => $expiresAt,
            ]);
            $acceptanceUrl = rtrim((string) config('platform.owner_invitation_url'), '?')
                .'?token='.urlencode($token);

            $mailDelivered = true;
            try {
                Mail::to($email)->send(new OrganizationOwnerInvitationMail($organization, $acceptanceUrl, $expiresAt));
            } catch (\Throwable $exception) {
                report($exception);
                $mailDelivered = false;
            }

            $this->auditLogs->insertLog($invitation, 'organization owner invitation created', [
                'record_id' => $invitation->id,
                'email' => $email,
                'expires_at' => $expiresAt,
                'mail_delivered' => $mailDelivered,
            ]);

            return [
                'invitation' => $invitation,
                'acceptance_url' => $acceptanceUrl,
                'mail_delivered' => $mailDelivered,
            ];
        });
    }

    public function accept(array $attributes): User
    {
        $tokenHash = hash('sha256', (string) $attributes['token']);
        $invitation = OrganizationOwnerInvitation::withoutGlobalScopes()
            ->where('token_hash', $tokenHash)
            ->first();

        if (! $invitation || $invitation->accepted_at || $invitation->revoked_at || $invitation->expires_at->isPast()) {
            throw ValidationException::withMessages(['token' => 'This owner invitation is invalid, expired, or has already been used.']);
        }

        $organization = Organization::query()->findOrFail($invitation->organization_id);

        return $this->tenantContext->run($organization, function () use ($attributes, $invitation): User {
            return DB::transaction(function () use ($attributes, $invitation): User {
                if (User::query()->where('email', $invitation->email)->exists()) {
                    throw ValidationException::withMessages(['token' => 'An account already exists for this invitation email.']);
                }

                $admin = Role::query()->firstOrCreate(
                    ['name' => 'Admin'],
                    ['description' => 'Full organization access']
                );
                $admin->permissions()->sync(Permission::query()->pluck('id'));
                $owner = User::create([
                    'role_id' => $admin->id,
                    'first_name' => $attributes['first_name'] ?? $invitation->first_name ?? 'Administrator',
                    'last_name' => $attributes['last_name'] ?? $invitation->last_name,
                    'email' => $invitation->email,
                    'birthday' => now()->toDateString(),
                    'password' => Hash::make((string) $attributes['password']),
                ]);
                $invitation->update(['accepted_at' => now(), 'accepted_by' => $owner->id]);
                $this->auditLogs->insertLog($invitation, 'organization owner invitation accepted', [
                    'record_id' => $invitation->id,
                    'owner_id' => $owner->id,
                ]);

                return $owner;
            });
        });
    }
}
