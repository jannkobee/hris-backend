<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class OvertimePolicy extends Model
{
    use BelongsToOrganization, HasUuids;

    protected $fillable = [
        'day_type',
        'multiplier',
        'is_active',
    ];

    protected $casts = [
        'multiplier' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}
