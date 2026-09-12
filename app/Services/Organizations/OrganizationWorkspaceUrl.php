<?php

namespace App\Services\Organizations;

use App\Models\Organization;

class OrganizationWorkspaceUrl
{
    public function login(Organization $organization): string
    {
        $frontend = rtrim((string) config('app.frontend_url', config('app.url')), '/');
        $domain = trim((string) config('tenancy.base_domain'));
        if ($domain === '' || $organization->slug === config('tenancy.default_slug')) {
            return $frontend.'/login';
        }

        $scheme = parse_url($frontend, PHP_URL_SCHEME) ?: 'https';
        $port = parse_url($frontend, PHP_URL_PORT);

        return $scheme.'://'.$organization->slug.'.'.$domain.($port ? ':'.$port : '').'/login';
    }
}
