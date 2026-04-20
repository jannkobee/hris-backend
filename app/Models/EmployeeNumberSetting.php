<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class EmployeeNumberSetting extends Model
{
    use HasUuids;

    protected $fillable = [
        'strategy',
        'prefix',
        'padding',
    ];
}
