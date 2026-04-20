<?php

namespace App\Services\EmployeeNumber;

interface EmployeeNumberStrategyInterface
{
    public function generate(array $settings): string;
}
