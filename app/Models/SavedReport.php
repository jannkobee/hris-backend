<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedReport extends Model
{
    use BelongsToOrganization, HasUuids;

    protected $fillable = [
        'name', 'report_type', 'filters', 'created_by', 'delivery_frequency',
        'delivery_period_days', 'delivery_recipients', 'next_delivery_at',
        'last_delivered_at', 'last_delivery_error',
    ];

    protected $casts = [
        'filters' => 'array',
        'delivery_recipients' => 'array',
        'next_delivery_at' => 'datetime',
        'last_delivered_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
