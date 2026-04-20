<?php

namespace App\Services\EmployeeNumber\Strategies;

use App\Models\Employee;
use App\Services\EmployeeNumber\EmployeeNumberStrategy;

class AutoIncrementStrategy extends EmployeeNumberStrategy
{
    public function generate(array $settings): string
    {
        $prefix  = $settings['prefix']  ?? 'EMP';
        $padding = (int) ($settings['padding'] ?? 4);

        $next = Employee::count() + 1;

        $make = function () use ($prefix, $padding, &$next) {
            return $prefix . '-' . str_pad($next++, $padding, '0', STR_PAD_LEFT);
        };

        return $this->ensureUnique($make(), $make);
    }
}
