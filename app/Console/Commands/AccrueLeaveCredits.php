<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Services\LeaveAccrual\LeaveCreditAccrualService;
use App\Tenancy\TenantContext;
use Illuminate\Console\Command;

class AccrueLeaveCredits extends Command
{
    protected $signature = 'leave-credits:accrue
        {--month= : Override the run month (1-12). Defaults to the current month.}
        {--year= : Override the run year. Defaults to the current year.}
        {--organization= : Run for one organization slug only. Defaults to every active organization.}';

    protected $description = 'Apply due leave-credit rules to every eligible employee. Safe to run repeatedly.';

    public function handle(LeaveCreditAccrualService $accrualService, TenantContext $context): int
    {
        $month = (int) ($this->option('month') ?: now()->month);
        $year = (int) ($this->option('year') ?: now()->year);
        $organizations = Organization::query()->where('status', Organization::STATUS_ACTIVE);
        if ($slug = $this->option('organization')) {
            $organizations->where('slug', $slug);
        }

        $result = ['credited' => 0, 'skipped' => 0, 'ineligible' => 0, 'failed' => 0];
        $organizations->get()->each(function (Organization $organization) use ($context, $accrualService, $month, $year, &$result): void {
            $organizationResult = $context->run(
                $organization,
                fn (): array => $accrualService->accrueDueEmployees($month, $year)
            );

            foreach ($result as $key => $value) {
                $result[$key] = $value + $organizationResult[$key];
            }
        });

        $this->info("Done. Credited: {$result['credited']}. Already credited: {$result['skipped']}. Ineligible: {$result['ineligible']}. Failed: {$result['failed']}.");

        return $result['failed'] === 0 ? self::SUCCESS : self::FAILURE;
    }
}
