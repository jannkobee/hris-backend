<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SubscriptionEvent extends Model
{
    use BelongsToOrganization, HasUuids;

    protected $fillable = [
        'event_type',
        'from_plan_code',
        'to_plan_code',
        'from_status',
        'to_status',
        'source',
        'reference',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];
}
