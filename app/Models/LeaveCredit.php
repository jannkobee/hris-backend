<?php

namespace App\Models;

use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class LeaveCredit extends Model
{
    use HasUuids, HasFilterScope;

    public $model_name = 'Leave Credit';

    protected $fillable = [
        'name',
        'description',
    ];

    protected array $filterable = [
        'name',
        'description',
    ];
}
