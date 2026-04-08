<?php

namespace App\Repository\EmploymentStatus;

use App\Models\EmploymentStatus;
use App\Repository\Base\BaseRepository;
use App\Services\AuditLog\AuditLogServiceInterface;
use App\Services\Utils\ResponseServiceInterface;

class EmploymentStatusRepository extends BaseRepository implements EmploymentStatusRepositoryInterface
{
    public function __construct(EmploymentStatus $model, ResponseServiceInterface $responseService, AuditLogServiceInterface $auditLogService)
    {
        parent::__construct($model, $responseService, $auditLogService);
    }
}
