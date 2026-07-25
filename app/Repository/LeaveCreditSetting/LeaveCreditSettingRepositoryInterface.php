<?php

namespace App\Repository\LeaveCreditSetting;

use App\Repository\Base\BaseRepositoryInterface;
use Illuminate\Support\Collection;

interface LeaveCreditSettingRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Active credit settings that should fire for the given calendar month (1-12).
     */
    public function dueForMonth(int $month): Collection;
}
