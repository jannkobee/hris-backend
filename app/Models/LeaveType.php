<?php

namespace App\Models;

use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    use HasUuids, HasFilterScope;

    public $model_name = 'Leave Type';

    protected $fillable = [
        'name',
        'description',
    ];

    protected array $filterable = [
        'name',
        'description',
    ];
}