<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class StatutoryRule extends Model
{
    use BelongsToOrganization, HasUuids;

    protected $fillable = [
        'name', 'country_code', 'effective_from', 'effective_until', 'rules',
    ];

    protected $casts = [
        'effective_from' => 'date:Y-m-d',
        'effective_until' => 'date:Y-m-d',
        'rules' => 'array',
    ];
}
