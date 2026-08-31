<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Services\Organizations\SubscriptionLifecycleService;
use App\Tenancy\TenantContext;
use Illuminate\Console\Command;

class ReconcileSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:reconcile {--organization= : Restrict reconciliation to one organization slug}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Apply trial-expiry and billing-period lifecycle transitions for organizations.';

    /**
     * Execute the console command.
     */
    public function handle(SubscriptionLifecycleService $subscriptions, TenantContext $tenantContext): int
    {
        $query = Organization::query();
        if ($slug = $this->option('organization')) {
            $query->where('slug', $slug);
        }

        $changed = 0;
        $query->where('status', Organization::STATUS_ACTIVE)->each(function (Organization $organization) use ($subscriptions, $tenantContext, &$changed): void {
            $updated = $tenantContext->run($organization, fn (): ?Organization => $subscriptions->reconcile($organization));
            if ($updated) {
                $changed++;
                $this->line("{$updated->slug}: {$updated->subscription_status}");
            }
        });

        $this->info("Subscription reconciliation completed. {$changed} organization(s) updated.");

        return self::SUCCESS;
    }
}
