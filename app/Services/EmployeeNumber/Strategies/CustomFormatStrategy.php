<?php

namespace App\Services\EmployeeNumber\Strategies;

use App\Models\Employee;
use App\Services\EmployeeNumber\EmployeeNumberStrategy;

class CustomFormatStrategy extends EmployeeNumberStrategy
{
    public function generate(array $settings): string
    {
        $format  = $settings['prefix'] ?? '{INC}';
        $padding = (int) ($settings['padding'] ?? 4);

        $next = Employee::count() + 1;

        $make = function () use ($format, $padding, &$next) {
            $incrementStr = $padding > 1 ? str_pad($next, $padding, '0', STR_PAD_LEFT) : (string)$next;

            $result = str_replace('{INC}', $incrementStr, $format);

            $result = str_replace('{YYYY}', now()->format('Y'), $result);
            $result = str_replace('{MM}', now()->format('m'), $result);

            $next++;

            return $result;
        };

        return $this->ensureUnique($make(), $make);
    }
}
