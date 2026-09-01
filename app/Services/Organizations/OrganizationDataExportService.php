<?php

namespace App\Services\Organizations;

use App\Jobs\GenerateOrganizationDataExport;
use App\Models\OrganizationDataExport;
use App\Models\User;
use App\Services\AuditLog\AuditLogServiceInterface;

class OrganizationDataExportService
{
    private AuditLogServiceInterface $auditLogs;

    public function __construct(AuditLogServiceInterface $auditLogs)
    {
        $this->auditLogs = $auditLogs;
    }

    public function request(User $user): OrganizationDataExport
    {
        $export = OrganizationDataExport::query()->create(['requested_by' => $user->getKey(), 'status' => OrganizationDataExport::STATUS_PENDING]);
        $this->auditLogs->insertLog($export, 'request organization data export', ['requested_by' => $user->getKey()]);
        GenerateOrganizationDataExport::dispatch($export);

        return $export;
    }
}
