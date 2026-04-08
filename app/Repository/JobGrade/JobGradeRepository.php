<?php

namespace App\Repository\JobGrade;

use App\Models\JobGrade;
use App\Repository\Base\BaseRepository;
use App\Services\AuditLog\AuditLogServiceInterface;
use App\Services\Utils\ResponseServiceInterface;

class JobGradeRepository extends BaseRepository implements JobGradeRepositoryInterface
{
    public function __construct(JobGrade $model, ResponseServiceInterface $responseService, AuditLogServiceInterface $auditLogService)
    {
        parent::__construct($model, $responseService, $auditLogService);
    }
}
