<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\CollectsOrganizationOwner;
use App\Models\Organization;
use App\Models\User;
use App\Services\Organizations\OrganizationInitializationService;
use App\Services\Organizations\OrganizationWorkspaceUrl;
use App\Tenancy\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SetupPlatform extends Command
{
    use CollectsOrganizationOwner;

    protected $signature = 'platform:setup
        {--organization= : Existing organization slug; defaults to the deployment workspace}
        {--admin-email= : Initial administrator email}
        {--admin-password-env= : Environment variable holding a password for non-interactive setup}';

    protected $description = 'Initialize an existing workspace and securely create its first administrator; safe to retry';

    public function handle(OrganizationInitializationService $initialization, TenantContext $context, OrganizationWorkspaceUrl $urls): int
    {
        $slug = $this->option('organization') ?: config('tenancy.default_slug');
        $organization = Organization::query()->where('slug', $slug)->first();
        if (! $organization || ! $organization->isActive()) {
            $this->error('Active workspace not found. Run migrations first, or use organizations:create for a new workspace.');

            return self::FAILURE;
        }

        $ownerExists = fn () => User::query()->where('is_active', true)->whereHas('role', fn ($query) => $query->where('name', 'Admin'))->exists();
        try {
            $attributes = $context->run($organization, $ownerExists) ? null : $this->ownerAttributes();
            DB::transaction(function () use ($organization, $initialization, $context, $ownerExists, $attributes): void {
                Organization::query()->whereKey($organization->id)->lockForUpdate()->firstOrFail();
                $initialization->initialize($organization);
                if ($attributes && ! $context->run($organization, $ownerExists)) {
                    $initialization->createAdministrator($organization, $attributes);
                }
            });
        } catch (ValidationException $exception) {
            return $this->reportOwnerValidation($exception);
        }

        $this->info('Workspace initialized. Existing accounts, passwords and customized defaults were preserved.');
        $this->line('Sign in: '.$urls->login($organization));
        if (in_array(config('mail.default'), ['log', 'array'], true)) {
            $this->warn('Email delivery is disabled for this mail transport. Password-reset and invitation emails will not reach an inbox.');
        }

        return self::SUCCESS;
    }
}
