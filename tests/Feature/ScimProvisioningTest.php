<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Role;
use App\Models\ScimToken;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScimProvisioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_scim_token_provisions_updates_and_deactivates_only_its_organization_users(): void
    {
        $organization = $this->organization('acme');
        $otherOrganization = $this->organization('other');
        $token = 'scim-test-token';

        app(TenantContext::class)->run($organization, function () use ($token): void {
            Role::create(['name' => 'User', 'slug' => 'user']);
            $creator = User::factory()->create();
            ScimToken::create([
                'name' => 'Okta',
                'token_hash' => hash('sha256', $token),
                'created_by' => $creator->getKey(),
            ]);
        });

        app(TenantContext::class)->run($otherOrganization, function (): void {
            $role = Role::create(['name' => 'User', 'slug' => 'user']);
            User::create([
                'role_id' => $role->id,
                'first_name' => 'Other',
                'last_name' => 'Employee',
                'email' => 'other@example.test',
                'birthday' => '1990-01-01',
                'password' => 'password',
            ]);
        });

        $response = $this->withToken($token)
            ->postJson(route('scim.users.store'), [
                'externalId' => 'okta-123',
                'userName' => 'jane@example.test',
                'name' => ['givenName' => 'Jane', 'familyName' => 'Doe'],
                'active' => true,
            ]);

        $response->assertCreated()
            ->assertJsonPath('externalId', 'okta-123')
            ->assertJsonPath('userName', 'jane@example.test');

        $userId = $response->json('id');

        $this->withToken($token)
            ->patchJson(route('scim.users.patch', $userId), [
                'Operations' => [
                    ['op' => 'replace', 'path' => 'active', 'value' => false],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('active', false);

        $this->withToken($token)
            ->getJson(route('scim.users.index', ['filter' => 'userName eq "jane@example.test"']))
            ->assertOk()
            ->assertJsonPath('totalResults', 1)
            ->assertJsonPath('Resources.0.id', $userId);

        app(TenantContext::class)->run($organization, function () use ($userId): void {
            $this->assertFalse(User::query()->findOrFail($userId)->is_active);
        });
    }

    private function organization(string $slug): Organization
    {
        return Organization::create([
            'slug' => $slug,
            'name' => ucfirst($slug),
            'timezone' => 'Asia/Manila',
            'plan_code' => Organization::PLAN_ENTERPRISE,
            'status' => Organization::STATUS_ACTIVE,
            'subscription_status' => Organization::SUBSCRIPTION_ACTIVE,
        ]);
    }
}
