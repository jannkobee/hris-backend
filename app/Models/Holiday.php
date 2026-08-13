<?php

namespace App\Models;

use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use HasFilterScope, HasUuids;

    public const TYPES = [
        'regular_holiday',
        'special_non_working_day',
        'special_working_day',
        'company_holiday',
    ];

    public $model_name = 'Holiday';

    protected $fillable = [
        'name',
        'date',
        'type',
        'description',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
    ];

    protected array $filterable = [
        'name',
        'date',
        'type',
        'description',
    ];
}
