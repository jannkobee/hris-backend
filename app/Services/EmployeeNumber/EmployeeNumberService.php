<?php

namespace App\Services\EmployeeNumber;

use App\Models\EmployeeNumberSetting;
use App\Services\EmployeeNumber\Strategies\AutoIncrementStrategy;
use App\Services\EmployeeNumber\Strategies\CustomFormatStrategy;
use App\Services\EmployeeNumber\Strategies\YearlyRandomStrategy;

class EmployeeNumberService implements EmployeeNumberServiceInterface
{
    private array $strategies = [
        'yearly_random'  => YearlyRandomStrategy::class,
        'auto_increment' => AutoIncrementStrategy::class,
        'custom_format'  => CustomFormatStrategy::class,
    ];

    public function generate(): string
    {
        $setting = EmployeeNumberSetting::firstOrCreate([], [
            'strategy' => 'yearly_random',
            'prefix'   => 'EMP',
            'padding'  => 4,
        ]);

        $strategyClass = $this->strategies[$setting->strategy] ?? YearlyRandomStrategy::class;

        return app($strategyClass)->generate($setting->toArray());
    }

    public function updateSettings(array $data): EmployeeNumberSetting
    {
        return EmployeeNumberSetting::updateOrCreate([], $data);
    }

    public function getSettings(): EmployeeNumberSetting
    {
        return EmployeeNumberSetting::firstOrCreate([], [
            'strategy' => 'yearly_random',
            'prefix'   => 'EMP',
            'padding'  => 4,
        ]);
    }

    public function availableStrategies(): array
    {
        return array_keys($this->strategies);
    }
}
