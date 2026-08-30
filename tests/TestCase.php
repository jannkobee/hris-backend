<?php

namespace Tests;

use App\Models\Organization;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        if (! in_array(RefreshDatabase::class, class_uses_recursive($this), true)) {
            return;
        }

        $organization = Organization::query()
            ->where('slug', config('tenancy.default_slug'))
            ->firstOrFail();

        app(TenantContext::class)->set($organization);
    }
}
