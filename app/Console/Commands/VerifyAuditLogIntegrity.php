<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Services\AuditLog\AuditLogServiceInterface;
use App\Tenancy\TenantContext;
use Illuminate\Console\Command;

class VerifyAuditLogIntegrity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit-logs:verify {--organization= : Restrict verification to one organization slug}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify the tenant audit-log hash chain without modifying audit records.';

    /**
     * Execute the console command.
     */
    public function handle(TenantContext $tenantContext, AuditLogServiceInterface $auditLogs): int
    {
        $organizations = Organization::query()
            ->where('status', Organization::STATUS_ACTIVE)
            ->when(
                $this->option('organization'),
                fn ($query, string $slug) => $query->where('slug', $slug),
            )
            ->get();

        if ($organizations->isEmpty()) {
            $this->error('No active organizations matched the requested scope.');

            return self::FAILURE;
        }

        $invalidOrganizations = 0;
        foreach ($organizations as $organization) {
            $integrity = $tenantContext->run(
                $organization,
                fn (): array => $auditLogs->verifyIntegrity(),
            );

            if (! $integrity['valid']) {
                $invalidOrganizations++;
                $this->error("{$organization->slug}: {$integrity['invalid_count']} integrity failure(s).");

                continue;
            }

            $this->info("{$organization->slug}: audit-log integrity verified.");
        }

        return $invalidOrganizations === 0 ? self::SUCCESS : self::FAILURE;
    }
}
