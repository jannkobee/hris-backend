<?php

namespace App\Models;

use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Position extends Model
{
    use HasUuids, HasFilterScope;

    public $model_name = 'Position';

    protected $fillable = [
        'department_id',
        'name',
        'description',
    ];

    protected array $filterable = [
        'name',
        'description',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
