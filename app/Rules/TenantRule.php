<?php

namespace App\Rules;

use App\Tenancy\TenantContext;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;

final class TenantRule
{
    public static function exists(string $table, string $column = 'id'): Exists
    {
        return Rule::exists($table, $column)->where(
            fn ($query) => $query->where('organization_id', app(TenantContext::class)->id())
        );
    }

    public static function unique(string $table, string $column): Unique
    {
        return Rule::unique($table, $column)->where(
            fn ($query) => $query->where('organization_id', app(TenantContext::class)->id())
        );
    }
}
