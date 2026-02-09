<?php

namespace App\Models;

use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class JobGrade extends Model
{
    use HasUuids, HasFilterScope;

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
