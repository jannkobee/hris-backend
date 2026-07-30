<?php

namespace App\Models;

use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ScheduledTask extends Model
{
    use HasUuids, HasFilterScope;

    public $model_name = 'Scheduled Task';

    public const FREQUENCIES = ['daily', 'weekly', 'monthly', 'yearly', 'custom'];

    protected $fillable = [
        'name',
        'description',
        'command',
        'frequency',
        'run_time',
        'run_days',
        'run_day_of_month',
        'run_months',
        'cron_expression',
        'timezone',
        'is_active',
        'last_run_at',
        'last_run_output',
        'next_run_at',
    ];

    protected $casts = [
        'run_days' => 'array',
        'run_months' => 'array',
        'is_active' => 'boolean',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
    ];

    protected array $filterable = [
        'name',
        'command',
        'frequency',
        'is_active',
    ];
}
