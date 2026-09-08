<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PlatformOperationLog extends Model
{
    use HasUuids;

    protected $fillable = ['action', 'payload', 'occurred_at'];

    protected $casts = [
        'payload' => 'array',
        'occurred_at' => 'datetime',
    ];
}
