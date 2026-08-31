<?php

namespace App\Services\Organizations;

use App\Models\Organization;
use App\Models\ScimToken;
use App\Models\User;
use App\Services\AuditLog\AuditLogServiceInterface;
use App\Tenancy\TenantContext;
use Laravel\Sanctum\PersonalAccessToken;

class PlatformSupportService
{
    private TenantContext $tenantContext;

    private AuditLogServiceInterface $auditLogs;

    public function __construct(TenantContext $tenantContext, AuditLogServiceInterface $auditLogs)
    {
        $this->tenantContext = $tenantContext;
        $this->auditLogs = $auditLogs;
    }

    public function setStatus(Organization $organization, string $status): Organization
    {
        $organization->update(['status' => $status]);
        $this->audit($organization, 'platform organization status changed', ['status' => $status]);

        return $organization->fresh();
    }

    public function revokeCredentials(Organization $organization, string $scope): array
    {
        return $this->tenantContext->run($organization, function () use ($scope): array {
            $result = ['scim_tokens' => 0, 'api_tokens' => 0];
            if (in_array($scope, ['scim', 'all'], true)) {
                $result['scim_tokens'] = ScimToken::query()->delete();
            }
            if (in_array($scope, ['api', 'all'], true)) {
                $result['api_tokens'] = PersonalAccessToken::query()
                    ->where('tokenable_type', User::class)
                    ->whereIn('tokenable_id', User::query()->select('id'))
                    ->delete();
            }
            $this->auditLogs->insertLog(Organization::class, 'platform credentials revoked', ['scope' => $scope, ...$result]);

            return $result;
        });
    }

    private function audit(Organization $organization, string $action, array $payload): void
    {
        $this->tenantContext->run($organization, fn () => $this->auditLogs->insertLog(Organization::class, $action, $payload));
    }
}
