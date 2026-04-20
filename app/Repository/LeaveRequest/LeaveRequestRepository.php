<?php

namespace App\Repository\LeaveRequest;

use App\Models\LeaveRequest;
use App\Repository\Base\BaseRepository;
use App\Services\AuditLog\AuditLogServiceInterface;
use App\Services\Utils\ResponseServiceInterface;

class LeaveRequestRepository extends BaseRepository implements LeaveRequestRepositoryInterface
{
    public function __construct(LeaveRequest $model, ResponseServiceInterface $responseService, AuditLogServiceInterface $auditLogService)
    {
        parent::__construct($model, $responseService, $auditLogService);
    }
}
