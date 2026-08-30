<?php

namespace Tests\Unit;

use Tests\TestCase;

class PermissionConfigTest extends TestCase
{
    public function test_role_templates_only_reference_catalog_permissions(): void
    {
        $catalogSlugs = collect(config('permissions.catalog'))
            ->flatMap(static fn (array $permissions): array => array_keys($permissions))
            ->values()
            ->all();
        $templates = config('permissions.role_templates');

        $this->assertNotEmpty($templates);
        $this->assertSame(
            $catalogSlugs,
            $templates['administrator']['permission_slugs']
        );

        foreach ($templates as $key => $template) {
            $this->assertNotEmpty($template['name'], "The {$key} role template must have a name.");
            $this->assertSame(
                [],
                array_values(array_diff($template['permission_slugs'], $catalogSlugs)),
                "The {$key} role template references permissions outside the catalog."
            );
            $this->assertSame(
                $template['permission_slugs'],
                array_values(array_unique($template['permission_slugs'])),
                "The {$key} role template contains duplicate permissions."
            );
        }
    }
}
