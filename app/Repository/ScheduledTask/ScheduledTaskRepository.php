<?php

namespace App\Repository\ScheduledTask;

use App\Models\ScheduledTask;
use App\Repository\Base\BaseRepository;
use App\Services\AuditLog\AuditLogServiceInterface;
use App\Services\Utils\ResponseServiceInterface;

class ScheduledTaskRepository extends BaseRepository implements ScheduledTaskRepositoryInterface
{
    public function __construct(ScheduledTask $model, ResponseServiceInterface $responseService, AuditLogServiceInterface $auditLogService)
    {
        parent::__construct($model, $responseService, $auditLogService);
    }
}
