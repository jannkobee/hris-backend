<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrganizationBrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_upload_and_retrieve_a_private_organization_logo(): void
    {
        Storage::fake('local');
        $admin = $this->userWithRole('Admin');

        $upload = $this->actingAs($admin, 'sanctum')->post(
            route('organization.branding.logo.upload'),
            ['logo' => UploadedFile::fake()->image('acme-logo.png', 240, 120)]
        )->assertAccepted();

        $organization = app(TenantContext::class)->organization()->fresh();
        $this->assertNotNull($organization->brand_logo_path);
        Storage::disk('local')->assertExists($organization->brand_logo_path);
        $upload
            ->assertJsonPath('data.name', $organization->name)
            ->assertJsonPath('data.brand_logo_url', $organization->brand_logo_url)
            ->assertJsonMissingPath('data.brand_logo_path')
            ->assertJsonMissingPath('data.brand_logo_disk');

        $this->actingAs($admin, 'sanctum')
            ->getJson(route('auth.auth-user'))
            ->assertOk()
            ->assertJsonPath('data.organization.brand_logo_url', $organization->brand_logo_url)
            ->assertJsonMissingPath('data.organization.brand_logo_path')
            ->assertJsonMissingPath('data.organization.brand_logo_disk');

        $this->actingAs($admin, 'sanctum')
            ->get(route('organization.branding.logo.show'))
            ->assertOk()
            ->assertHeader('content-type', 'image/png');
    }

    public function test_replacing_and_removing_a_logo_cleans_up_the_previous_private_file(): void
    {
        Storage::fake('local');
        $admin = $this->userWithRole('Admin');

        $this->actingAs($admin, 'sanctum')->post(
            route('organization.branding.logo.upload'),
            ['logo' => UploadedFile::fake()->image('first.jpg', 120, 120)]
        )->assertAccepted();
        $firstOrganization = app(TenantContext::class)->organization()->fresh();
        $oldPath = $firstOrganization->brand_logo_path;
        $oldUrl = $firstOrganization->brand_logo_url;

        $this->actingAs($admin, 'sanctum')->post(
            route('organization.branding.logo.upload'),
            ['logo' => UploadedFile::fake()->image('second.webp', 120, 120)]
        )->assertAccepted();
        $organization = app(TenantContext::class)->organization()->fresh();

        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($organization->brand_logo_path);
        $this->assertNotSame($oldUrl, $organization->brand_logo_url);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson(route('organization.branding.logo.delete'))
            ->assertOk()
            ->assertJsonPath('data.brand_logo_url', null);

        Storage::disk('local')->assertMissing($organization->brand_logo_path);
        $this->assertNull($organization->fresh()->brand_logo_path);
    }

    public function test_regular_user_cannot_change_the_organization_logo_but_can_read_current_tenant_branding(): void
    {
        Storage::fake('local');
        $admin = $this->userWithRole('Admin');
        $employee = $this->userWithRole('User');

        $this->actingAs($admin, 'sanctum')->post(
            route('organization.branding.logo.upload'),
            ['logo' => UploadedFile::fake()->image('logo.png', 120, 120)]
        )->assertAccepted();

        $this->actingAs($employee, 'sanctum')
            ->post(route('organization.branding.logo.upload'), [
                'logo' => UploadedFile::fake()->image('unapproved.png', 120, 120),
            ])
            ->assertForbidden();

        $this->actingAs($employee, 'sanctum')
            ->deleteJson(route('organization.branding.logo.delete'))
            ->assertForbidden();

        $this->actingAs($employee, 'sanctum')
            ->get(route('organization.branding.logo.show'))
            ->assertOk()
            ->assertHeader('content-type', 'image/png');
    }

    public function test_logo_validation_rejects_non_image_uploads(): void
    {
        Storage::fake('local');
        $admin = $this->userWithRole('Admin');

        $this->actingAs($admin, 'sanctum')
            ->withHeader('Accept', 'application/json')
            ->post(route('organization.branding.logo.upload'), [
                'logo' => UploadedFile::fake()->create('logo.pdf', 20, 'application/pdf'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('logo');
    }

    private function userWithRole(string $roleName): User
    {
        $role = Role::create(['name' => $roleName]);

        return User::factory()->create(['role_id' => $role->id]);
    }
}
