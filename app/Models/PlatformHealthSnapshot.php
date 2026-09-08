<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PlatformHealthSnapshot extends Model
{
    use HasUuids;

    protected $fillable = ['status', 'checks', 'captured_at'];

    protected $casts = [
        'checks' => 'array',
        'captured_at' => 'datetime',
    ];
}
