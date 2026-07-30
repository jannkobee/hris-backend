<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\LeaveCredit;
use App\Models\LeaveCreditLog;
use App\Models\LeaveCreditSetting;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class AccrueLeaveCredits extends Command
{
    protected $signature = 'leave-credits:accrue
        {--month= : Override the run month (1-12). Defaults to the current month.}
        {--year= : Override the run year. Defaults to the current year.}';

    protected $description = 'Apply every active leave credit setting scheduled for the given month to all eligible employees.';

    public function handle(): int
    {
        $month = (int) ($this->option('month') ?: now()->month);
        $year = (int) ($this->option('year') ?: now()->year);

        $settings = LeaveCreditSetting::query()
            ->where('is_active', true)
            ->whereJsonContains('run_months', $month)
            ->get();

        if ($settings->isEmpty()) {
            $this->info("No active leave credit settings are scheduled for month {$month}.");
            return self::SUCCESS;
        }

        $credited = 0;
        $skipped = 0;

        foreach ($settings as $setting) {
            $this->info("Applying \"{$setting->name}\" ({$setting->credit_amount} credits)...");

            // ASSUMPTION: every employee hired on or before today is
            // eligible. If employees with a terminated/inactive
            // employment_status should be excluded, add that condition
            // here, e.g.:
            //   ->whereHas('employmentStatus', fn ($q) => $q->where('is_active', true))
            Employee::query()
                ->where('hire_date', '<=', now())
                ->chunkById(200, function ($employees) use ($setting, $month, $year, &$credited, &$skipped) {
                    foreach ($employees as $employee) {
                        $alreadyRun = LeaveCreditLog::where([
                            'leave_credit_setting_id' => $setting->id,
                            'employee_id' => $employee->id,
                            'year' => $year,
                            'month' => $month,
                        ])->exists();

                        if ($alreadyRun) {
                            $skipped++;
                            continue;
                        }

                        try {
                            DB::transaction(function () use ($setting, $employee, $month, $year) {
                                // Written first: if this employee/setting/
                                // month combo has already been logged by a
                                // concurrent run, the unique index below
                                // rejects the insert and we bail out via
                                // the catch below instead of double
                                // crediting.
                                LeaveCreditLog::create([
                                    'leave_credit_setting_id' => $setting->id,
                                    'employee_id' => $employee->id,
                                    'leave_type_id' => $setting->leave_type_id,
                                    'year' => $year,
                                    'month' => $month,
                                    'credited_amount' => $setting->credit_amount,
                                ]);

                                $ledger = LeaveCredit::where([
                                    'employee_id' => $employee->id,
                                    'leave_type_id' => $setting->leave_type_id,
                                    'year' => $year,
                                ])->lockForUpdate()->first();

                                if (! $ledger) {
                                    $ledger = new LeaveCredit([
                                        'employee_id' => $employee->id,
                                        'leave_type_id' => $setting->leave_type_id,
                                        'year' => $year,
                                        'used' => 0,
                                        'total_earned' => 0,
                                    ]);
                                }

                                $ledger->total_earned = (float) $ledger->total_earned + (float) $setting->credit_amount;
                                $ledger->save();
                            });

                            $credited++;
                        } catch (QueryException $e) {
                            // Unique constraint hit on leave_credit_logs —
                            // another run already applied this credit.
                            $skipped++;
                        }
                    }
                });
        }

        $this->info("Done. Credited: {$credited}. Skipped (already applied): {$skipped}.");

        return self::SUCCESS;
    }
}
