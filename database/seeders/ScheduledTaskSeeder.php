<?php

namespace Database\Seeders;

use App\Services\LeaveAccrual\LeaveAccrualScheduleSyncer;
use Illuminate\Database\Seeder;

class ScheduledTaskSeeder extends Seeder
{
    /**
     * Creates (or repairs) the "Leave Accrual" scheduled task using the same
     * logic the observer runs on every Leave Credit Setting change — so
     * running this seeder can never leave it in a different state than
     * normal usage would.
     */
    public function run(LeaveAccrualScheduleSyncer $syncer): void
    {
        $syncer->sync();
    }
}
