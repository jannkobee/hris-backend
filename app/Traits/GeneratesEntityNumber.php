<?php

namespace App\Traits;

trait GeneratesEntityNumber
{
    /**
     * Generate a unique entity number like EMP-2025-123456.
     *
     * @param  class-string  $model      The Eloquent model class to check uniqueness against
     * @param  string        $prefix     e.g. "EMP"
     * @param  string        $column     The DB column to check, default 'employee_no'
     */
    protected function makeUniqueNo(string $model, string $prefix, string $column = 'employee_no'): string
    {
        $year = now()->format('Y');

        do {
            $rand = random_int(100000, 999999);
            $number = "{$prefix}-{$year}-{$rand}";
        } while ($model::where($column, $number)->exists());

        return $number;
    }
}
