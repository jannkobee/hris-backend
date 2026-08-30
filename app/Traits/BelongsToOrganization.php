<?php

namespace App\Traits;

use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

trait BelongsToOrganization
{
    public static function bootBelongsToOrganization(): void
    {
        static::addGlobalScope(new OrganizationScope);

        static::saving(function (Model $model): void {
            $context = app(TenantContext::class);
            $organizationId = $context->id();

            if ($organizationId === null) {
                throw new LogicException('Tenant-owned records cannot be saved without an organization context.');
            }

            $modelOrganizationId = $model->getAttribute('organization_id');

            if (! $model->exists && $modelOrganizationId === null) {
                $model->setAttribute('organization_id', $organizationId);

                return;
            }

            if (! $context->belongsToCurrentOrganization((string) $modelOrganizationId)) {
                throw new LogicException('Tenant-owned records cannot be moved between organizations.');
            }

            if ($model->exists && $model->isDirty('organization_id')) {
                throw new LogicException('The organization of an existing record cannot be changed.');
            }
        });

        static::deleting(function (Model $model): void {
            if (! app(TenantContext::class)->belongsToCurrentOrganization(
                $model->getAttribute('organization_id')
            )) {
                throw new LogicException('Tenant-owned records cannot be deleted outside their organization context.');
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
