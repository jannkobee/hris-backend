<?php

namespace App\Repository\LeaveType;

use App\Models\LeaveType;
use App\Repository\Base\BaseRepository;
use App\Services\AuditLog\AuditLogServiceInterface;
use App\Services\Utils\ResponseServiceInterface;

class LeaveTypeRepository extends BaseRepository implements LeaveTypeRepositoryInterface
{
    public function __construct(LeaveType $model, ResponseServiceInterface $responseService, AuditLogServiceInterface $auditLogService)
    {
        parent::__construct($model, $responseService, $auditLogService);
    }
}
