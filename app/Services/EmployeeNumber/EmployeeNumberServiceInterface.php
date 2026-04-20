<?php

namespace App\Services\EmployeeNumber;

use App\Models\EmployeeNumberSetting;

interface EmployeeNumberServiceInterface
{
    public function generate(): string;

    public function updateSettings(array $data): EmployeeNumberSetting;

    public function getSettings(): EmployeeNumberSetting;

    public function availableStrategies(): array;
}
