<?php

namespace App\Services\EmployeeNumber\Strategies;

use App\Services\EmployeeNumber\EmployeeNumberStrategy;

class YearlyRandomStrategy extends EmployeeNumberStrategy
{
    public function generate(array $settings): string
    {
        $prefix = $settings['prefix'] ?? 'EMP';
        $year   = now()->format('Y');

        $make = fn() => $prefix . '-' . $year . '-' . random_int(100000, 999999);

        return $this->ensureUnique($make(), $make);
    }
}
