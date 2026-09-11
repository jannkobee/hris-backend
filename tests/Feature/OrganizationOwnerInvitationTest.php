<?php

namespace Tests\Feature;

use App\Mail\OrganizationOwnerInvitationMail;
use App\Models\Organization;
use App\Models\OrganizationOwnerInvitation;
use App\Models\User;
use App\Services\Organizations\OrganizationOwnerInvitationService;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrganizationOwnerInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_invitation_is_single_use_and_creates_the_organization_admin(): void
    {
        Mail::fake();
        $organization = Organization::create([
            'slug' => 'invite-test',
            'name' => 'Invitation Test',
            'timezone' => 'Asia/Manila',
            'country_code' => 'PH',
            'plan_code' => Organization::PLAN_ENTERPRISE,
            'status' => Organization::STATUS_ACTIVE,
            'subscription_status' => Organization::SUBSCRIPTION_TRIALING,
        ]);

        $result = app(OrganizationOwnerInvitationService::class)->invite($organization, [
            'email' => 'owner@example.test',
            'first_name' => 'Owner',
            'last_name' => 'Admin',
        ]);
        parse_str((string) parse_url($result['acceptance_url'], PHP_URL_QUERY), $query);

        $owner = app(OrganizationOwnerInvitationService::class)->accept([
            'token' => $query['token'],
            'password' => 'OwnerPassword!2026',
        ]);

        Mail::assertSent(OrganizationOwnerInvitationMail::class, fn ($mail) => $mail->hasTo('owner@example.test'));
        Mail::assertSent(OrganizationOwnerInvitationMail::class, fn($mail) => $mail->hasTo('owner@example.test'));
        $this->assertSame($organization->id, $owner->organization_id);
        $this->assertSame('owner@example.test', $owner->email);
        app(TenantContext::class)->run($organization, function () use ($owner): void {
            $this->assertSame('Admin', $owner->fresh()->role->name);
            $this->assertTrue(User::query()->whereKey($owner->id)->exists());
        });
        $this->assertNotNull(OrganizationOwnerInvitation::withoutGlobalScopes()->firstOrFail()->accepted_at);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(OrganizationOwnerInvitationService::class)->accept([
            'token' => $query['token'],
            'password' => 'OwnerPassword!2026',
        ]);
    }

    public function test_owner_invitation_can_be_resent_and_revoked(): void
    {
        Mail::fake();
        $organization = Organization::create([
            'slug' => 'invite-manage-test',
            'name' => 'Invitation Manage Test',
            'timezone' => 'Asia/Manila',
            'country_code' => 'PH',
            'plan_code' => Organization::PLAN_ENTERPRISE,
            'status' => Organization::STATUS_ACTIVE,
            'subscription_status' => Organization::SUBSCRIPTION_TRIALING,
        ]);

        $service = app(OrganizationOwnerInvitationService::class);
        $result = $service->invite($organization, [
            'email' => 'manage@example.test',
            'first_name' => 'Manage',
            'last_name' => 'Owner',
        ]);
        /** @var OrganizationOwnerInvitation $invitation */
        $invitation = $result['invitation'];

        $this->assertNull($invitation->revoked_at);

        // Resend
        $resent = $service->resend($organization, $invitation);
        $this->assertTrue($resent['mail_delivered']);
        $this->assertNotSame($result['acceptance_url'], $resent['acceptance_url']);

        // Revoke
        $revoked = $service->revoke($organization, $invitation);
        $this->assertNotNull($revoked->revoked_at);

        // Attempting to accept revoked invitation should fail
        parse_str((string) parse_url($resent['acceptance_url'], PHP_URL_QUERY), $query);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $service->accept([
            'token' => $query['token'],
            'password' => 'Password123!',
        ]);
    }
}
