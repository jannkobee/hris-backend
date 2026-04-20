<?php

namespace App\Repository\LeaveCredit;

use App\Models\LeaveCredit;
use App\Repository\Base\BaseRepository;
use App\Services\AuditLog\AuditLogServiceInterface;
use App\Services\Utils\ResponseServiceInterface;

class LeaveCreditRepository extends BaseRepository implements LeaveCreditRepositoryInterface
{
    public function __construct(LeaveCredit $model, ResponseServiceInterface $responseService, AuditLogServiceInterface $auditLogService)
    {
        parent::__construct($model, $responseService, $auditLogService);
    }
}
