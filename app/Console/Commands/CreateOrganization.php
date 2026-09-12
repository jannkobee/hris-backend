<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\CollectsOrganizationOwner;
use App\Models\Organization;
use App\Services\Organizations\OrganizationProvisioningService;
use App\Services\Organizations\OrganizationWorkspaceUrl;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

class CreateOrganization extends Command
{
    use CollectsOrganizationOwner;

    protected $signature = 'organizations:create
        {slug : Unique workspace slug}
        {name : Organization display name}
        {--plan=growth : Configured subscription plan}
        {--country=PH : Two-letter country code}
        {--timezone=Asia/Manila : Organization timezone}
        {--admin-email= : Initial administrator email}
        {--admin-password-env= : Environment variable holding a password for automation}
        {--admin-password= : Deprecated; use the private prompt instead}';

    protected $description = 'Create a complete workspace with defaults, subscription and a securely provisioned administrator';

    public function handle(OrganizationProvisioningService $provisioning, OrganizationWorkspaceUrl $urls): int
    {
        $slug = strtolower(trim((string) $this->argument('slug')));
        if (Organization::query()->where('slug', $slug)->exists()) {
            $this->error('Workspace already exists. Use platform:setup --organization='.$slug.' to initialize missing defaults or its first administrator.');

            return self::FAILURE;
        }

        try {
            $organization = $provisioning->provision([
                'slug' => $slug,
                'name' => trim((string) $this->argument('name')),
                'plan_code' => strtolower((string) $this->option('plan')),
                'country_code' => strtoupper((string) $this->option('country')),
                'timezone' => (string) $this->option('timezone'),
                ...$this->ownerAttributes(),
            ]);
        } catch (ValidationException $exception) {
            return $this->reportOwnerValidation($exception);
        }

        $this->info('Organization created with its administrator, permissions and HR defaults.');
        $this->line('Sign in: '.$urls->login($organization));
        $this->warn('Verify this workspace hostname has DNS and HTTPS configured before sharing the link.');

        return self::SUCCESS;
    }
}
