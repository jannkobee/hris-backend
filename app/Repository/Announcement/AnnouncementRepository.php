<?php

namespace App\Repository\Announcement;

use App\Models\Announcement;
use App\Repository\Base\BaseRepository;
use App\Services\AuditLog\AuditLogServiceInterface;
use App\Services\Utils\ResponseServiceInterface;
use Illuminate\Http\JsonResponse;

class AnnouncementRepository extends BaseRepository implements AnnouncementRepositoryInterface
{
    public function __construct(Announcement $model, ResponseServiceInterface $responseService, AuditLogServiceInterface $auditLogService)
    {
        parent::__construct($model, $responseService, $auditLogService);
    }

    public function create(array $attributes): JsonResponse
    {
        $attributes['created_by'] = auth()->id();

        return parent::create($attributes);
    }
}
