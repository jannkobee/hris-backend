<?php

namespace App\Services\Tenancy;

use App\Models\Organization;
use Illuminate\Http\Request;

class TenantResolver
{
    public function resolve(Request $request): ?Organization
    {
        $slug = $this->slugForHost($request->getHost());

        if ($slug === null) {
            return null;
        }

        return Organization::query()->where('slug', $slug)->first();
    }

    public function slugForHost(string $host): ?string
    {
        $host = strtolower(rtrim(trim($host), '.'));
        $baseDomain = strtolower(rtrim(trim((string) config('tenancy.base_domain')), '.'));
        $defaultSlug = strtolower(trim((string) config('tenancy.default_slug', 'legacy')));

        if ($baseDomain === '') {
            return $this->isValidSlug($defaultSlug) ? $defaultSlug : null;
        }

        if ($host === $baseDomain) {
            return $this->isValidSlug($defaultSlug) ? $defaultSlug : null;
        }

        $suffix = '.'.$baseDomain;
        if (! str_ends_with($host, $suffix)) {
            return null;
        }

        $slug = substr($host, 0, -strlen($suffix));

        return $this->isValidSlug($slug) ? $slug : null;
    }

    private function isValidSlug(string $slug): bool
    {
        return (bool) preg_match('/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/', $slug);
    }
}
