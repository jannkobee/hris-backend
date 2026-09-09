<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Services\Organizations\StripeBillingService;
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
        $failed = 0;
        $stripe = app(StripeBillingService::class);
        $query->where('status', Organization::STATUS_ACTIVE)->each(function (Organization $organization) use ($subscriptions, $stripe, $tenantContext, &$changed, &$failed): void {
            $updated = $tenantContext->run($organization, fn (): ?Organization => $subscriptions->reconcile($organization));
            if ($updated) {
                $changed++;
                $this->line("{$updated->slug}: {$updated->subscription_status}");
            }
            if ($organization->plan_code === 'growth' && $organization->billing_provider === 'stripe') {
                try {
                    $tenantContext->run($organization, fn () => $stripe->syncSubscriptionQuantity($organization));
                } catch (\Throwable $exception) {
                    $failed++;
                    $this->error("{$organization->slug}: Stripe synchronization failed; next scheduled run will retry.");
                    \Illuminate\Support\Facades\Log::warning('Stripe synchronization failed', ['organization_id' => $organization->id, 'exception_type' => get_class($exception)]);
                }
            }
        });

        $this->info("Subscription reconciliation completed. {$changed} organization(s) updated.");

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
