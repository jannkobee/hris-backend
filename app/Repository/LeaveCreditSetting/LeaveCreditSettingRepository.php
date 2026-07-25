<?php

namespace App\Repository\LeaveCreditSetting;

use App\Models\LeaveCreditSetting;
use App\Repository\Base\BaseRepository;
use App\Services\AuditLog\AuditLogServiceInterface;
use App\Services\Utils\ResponseServiceInterface;
use Illuminate\Support\Collection;

class LeaveCreditSettingRepository extends BaseRepository implements LeaveCreditSettingRepositoryInterface
{
    public function __construct(LeaveCreditSetting $model, ResponseServiceInterface $responseService, AuditLogServiceInterface $auditLogService)
    {
        parent::__construct($model, $responseService, $auditLogService);
    }

    public function dueForMonth(int $month): Collection
    {
        return $this->model->newQuery()
            ->where('is_active', true)
            ->whereJsonContains('run_months', $month)
            ->get();
    }
}
