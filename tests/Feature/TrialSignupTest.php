<?php

namespace Tests\Feature;

use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrialSignupTest extends TestCase
{
    use RefreshDatabase;

    public function test_trial_signup_generates_a_workspace_slug_from_the_organization_name(): void
    {
        $this->postJson(route('public-apis.trial-signups.store'), $this->payload([
            'organization_name' => 'Acme & Sons HR!',
            'slug' => 'customer-supplied-value',
        ]))
            ->assertCreated()
            ->assertJsonPath('data.organization.slug', 'acme-sons-hr');

        $this->assertDatabaseHas('organizations', [
            'name' => 'Acme & Sons HR!',
            'slug' => 'acme-sons-hr',
        ]);
    }

    public function test_trial_signup_appends_a_suffix_when_the_generated_workspace_slug_is_taken(): void
    {
        Organization::create([
            'slug' => 'acme-sons-hr',
            'name' => 'Existing Acme',
            'timezone' => 'Asia/Manila',
            'country_code' => 'PH',
            'plan_code' => Organization::PLAN_GROWTH,
            'status' => Organization::STATUS_ACTIVE,
            'subscription_status' => Organization::SUBSCRIPTION_TRIALING,
            'trial_ends_at' => now()->addDays(14),
        ]);

        $this->postJson(route('public-apis.trial-signups.store'), $this->payload([
            'organization_name' => 'Acme Sons HR',
        ]))
            ->assertCreated()
            ->assertJsonPath('data.organization.slug', 'acme-sons-hr-2');
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'organization_name' => 'Example Organization',
            'country_code' => 'PH',
            'timezone' => 'Asia/Manila',
            'plan_code' => Organization::PLAN_GROWTH,
            'first_name' => 'Ada',
            'last_name' => 'Admin',
            'email' => 'ada@example.test',
            'password' => 'TrialPassword!123',
            'password_confirmation' => 'TrialPassword!123',
            'terms_accepted' => true,
        ], $overrides);
    }
}
