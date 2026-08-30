<?php

namespace App\Repository\ShiftTemplate;

use App\Models\ShiftTemplate;
use App\Repository\Base\BaseRepository;
use App\Services\AuditLog\AuditLogServiceInterface;
use App\Services\Utils\ResponseServiceInterface;

class ShiftTemplateRepository extends BaseRepository implements ShiftTemplateRepositoryInterface
{
    public function __construct(ShiftTemplate $model, ResponseServiceInterface $responseService, AuditLogServiceInterface $auditLogService)
    {
        parent::__construct($model, $responseService, $auditLogService);
    }
}
