<?php

namespace App\Tenancy;

use App\Models\Organization;
use LogicException;

class TenantContext
{
    private ?Organization $organization = null;

    public function set(Organization $organization): void
    {
        $this->organization = $organization;
    }

    public function clear(): void
    {
        $this->organization = null;
    }

    public function hasOrganization(): bool
    {
        return $this->organization !== null;
    }

    public function organizationOrNull(): ?Organization
    {
        return $this->organization;
    }

    public function organization(): Organization
    {
        return $this->organization
            ?? throw new LogicException('No organization has been resolved for the current execution context.');
    }

    public function id(): ?string
    {
        return $this->organization?->getKey();
    }

    public function belongsToCurrentOrganization(?string $organizationId): bool
    {
        $currentId = $this->id();

        return $currentId !== null
            && $organizationId !== null
            && hash_equals($currentId, $organizationId);
    }

    public function run(Organization $organization, callable $callback): mixed
    {
        $previousOrganization = $this->organization;
        $this->set($organization);

        try {
            return $callback();
        } finally {
            $this->organization = $previousOrganization;
        }
    }
}
