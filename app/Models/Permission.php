<?php

namespace App\Models;

use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasUuids, HasFilterScope;

    public $model_name = 'Permission';

    protected $fillable = [
        'name',
        'slug'
    ];

    protected array $filterable = [
        'name',
    ];
}
