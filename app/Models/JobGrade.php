<?php

namespace App\Models;

use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class JobGrade extends Model
{
    use HasUuids, HasFilterScope;

    public $model_name = 'Job Grade';

    protected $fillable = [
        'name',
        'code',
        'description',
        'min_salary',
        'max_salary',
    ];

    protected array $filterable = [
        'name',
        'code',
        'description',
    ];
}
