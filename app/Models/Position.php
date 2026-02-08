<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Position extends Model
{
    use HasUuids;

    protected $fillable = [
        'department_id',
        'name',
        'description',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
