<?php

namespace App\Repository\LeaveCreditSetting;

use App\Models\LeaveCreditSetting;
use App\Repository\Base\BaseRepository;
use App\Services\AuditLog\AuditLogServiceInterface;
use App\Services\Utils\ResponseServiceInterface;
use App\Services\LeaveAccrual\LeaveAccrualScheduleSyncer;
use Illuminate\Support\Collection;
use Illuminate\Http\JsonResponse;

class LeaveCreditSettingRepository extends BaseRepository implements LeaveCreditSettingRepositoryInterface
{
    private LeaveAccrualScheduleSyncer $syncer;

    public function __construct(
        LeaveCreditSetting $model,
        ResponseServiceInterface $responseService,
        AuditLogServiceInterface $auditLogService,
        LeaveAccrualScheduleSyncer $syncer
    ) {
        parent::__construct($model, $responseService, $auditLogService);
        $this->syncer = $syncer;
    }

    public function dueForMonth(int $month): Collection
    {
        return $this->model->newQuery()
            ->where('is_active', true)
            ->whereJsonContains('run_months', $month)
            ->get();
    }

    public function create(array $attributes): JsonResponse
    {
        $response = parent::create($attributes);
        $this->syncer->sync();
        return $response;
    }

    public function update(array $attributes, string|int $id): JsonResponse
    {
        $response = parent::update($attributes, $id);
        $this->syncer->sync();
        return $response;
    }

    public function delete(string $id): JsonResponse
    {
        $response = parent::delete($id);
        $this->syncer->sync();
        return $response;
    }
}
