<?php

namespace App\Services\EmployeeNumber;

use App\Models\Employee;

abstract class EmployeeNumberStrategy implements EmployeeNumberStrategyInterface
{
    protected function ensureUnique(string $candidate, callable $generator): string
    {
        while (Employee::where('employee_no', $candidate)->exists()) {
            $candidate = $generator();
        }

        return $candidate;
    }
}
