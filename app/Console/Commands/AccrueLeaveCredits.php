<?php

namespace App\Console\Commands;

use App\Services\LeaveAccrual\LeaveCreditAccrualService;
use Illuminate\Console\Command;

class AccrueLeaveCredits extends Command
{
    protected $signature = 'leave-credits:accrue
        {--month= : Override the run month (1-12). Defaults to the current month.}
        {--year= : Override the run year. Defaults to the current year.}';

    protected $description = 'Apply due leave-credit rules to every eligible employee. Safe to run repeatedly.';

    public function handle(LeaveCreditAccrualService $accrualService): int
    {
        $month = (int) ($this->option('month') ?: now()->month);
        $year = (int) ($this->option('year') ?: now()->year);
        $result = $accrualService->accrueDueEmployees($month, $year);

        $this->info("Done. Credited: {$result['credited']}. Already credited: {$result['skipped']}. Ineligible: {$result['ineligible']}. Failed: {$result['failed']}.");

        return $result['failed'] === 0 ? self::SUCCESS : self::FAILURE;
    }
}
