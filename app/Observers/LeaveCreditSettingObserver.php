<?php

namespace App\Observers;

use App\Models\LeaveCreditSetting;
use App\Services\LeaveAccrual\LeaveAccrualScheduleSyncer;

class LeaveCreditSettingObserver
{
    public function __construct(private LeaveAccrualScheduleSyncer $syncer) {}

    public function saved(LeaveCreditSetting $setting): void
    {
        $this->syncer->sync();
    }

    public function deleted(LeaveCreditSetting $setting): void
    {
        $this->syncer->sync();
    }
}
