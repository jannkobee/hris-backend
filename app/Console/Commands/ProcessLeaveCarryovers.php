<?php

namespace App\Console\Commands;

use App\Models\LeaveCredit;
use App\Models\LeaveCreditCarryover;
use App\Models\LeaveCreditSetting;
use App\Models\Organization;
use App\Tenancy\TenantContext;
use Illuminate\Console\Command;

class ProcessLeaveCarryovers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leave-credits:process-carryovers {--year=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Carry capped leave balances forward and expire due carry-over balances.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $year = (int) ($this->option('year') ?: now()->year);
        Organization::query()->where('status', Organization::STATUS_ACTIVE)->each(function (Organization $organization) use ($year): void {
            app(TenantContext::class)->run($organization, function () use ($year): void {
                $this->expireDueCarryovers();
                LeaveCredit::query()->where('year', $year - 1)->get()->each(function (LeaveCredit $source) use ($year): void {
                    $policy = LeaveCreditSetting::query()->where('leave_type_id', $source->leave_type_id)->where('is_active', true)->first();
                    $amount = min(max(0, $source->remaining), (float) ($policy?->carry_over_limit ?? 0));
                    if ($amount <= 0 || LeaveCreditCarryover::query()->where('leave_credit_id', $source->id)->exists()) {
                        return;
                    }
                    $target = LeaveCredit::firstOrCreate(['employee_id' => $source->employee_id, 'leave_type_id' => $source->leave_type_id, 'year' => $year], ['total_earned' => 0, 'used' => 0]);
                    $target->increment('total_earned', $amount);
                    LeaveCreditCarryover::create(['leave_credit_id' => $source->id, 'target_leave_credit_id' => $target->id, 'amount' => $amount, 'expires_on' => $policy?->carry_over_expiry_month ? now()->setYear($year)->setMonth($policy->carry_over_expiry_month)->endOfMonth() : null]);
                });
            });
        });
        $this->info('Leave carry-overs processed.');

        return self::SUCCESS;
    }

    private function expireDueCarryovers(): void
    {
        LeaveCreditCarryover::query()->whereNull('expired_at')->whereNotNull('expires_on')->whereDate('expires_on', '<', today())->each(function (LeaveCreditCarryover $carryover): void {
            $target = LeaveCredit::query()->find($carryover->target_leave_credit_id);
            if ($target) {
                $target->decrement('total_earned', min((float) $carryover->amount, max(0, $target->remaining)));
            } $carryover->update(['expired_at' => now()]);
        });
    }
}
