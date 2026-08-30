<?php

namespace App\Repository\LeaveCreditSetting;

use App\Models\LeaveCreditSetting;
use App\Repository\Base\BaseRepository;
use App\Services\AuditLog\AuditLogServiceInterface;
use App\Services\LeaveAccrual\LeaveAccrualScheduleSyncer;
use App\Services\Utils\ResponseServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

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
        $attributes = $this->normalizeInitialGrant($attributes, true);
        $response = parent::create($attributes);
        $this->syncer->sync();

        return $response;
    }

    public function update(array $attributes, string|int $id): JsonResponse
    {
        $attributes = $this->normalizeInitialGrant($attributes);
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

    private function normalizeInitialGrant(array $attributes, bool $isCreating = false): array
    {
        if (! $isCreating && ! array_key_exists('grant_on_hire', $attributes)) {
            return $attributes;
        }

        $attributes['grant_on_hire'] = filter_var(
            $attributes['grant_on_hire'] ?? false,
            FILTER_VALIDATE_BOOLEAN
        );
        $attributes['initial_credit_amount'] = $attributes['grant_on_hire']
            ? (float) ($attributes['initial_credit_amount'] ?? 0)
            : 0;

        return $attributes;
    }
}
