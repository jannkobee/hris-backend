<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookSubscription extends Model
{
    use BelongsToOrganization, HasUuids;

    protected $fillable = [
        'name', 'url', 'event_types', 'signing_secret', 'is_active',
        'created_by', 'last_delivered_at', 'last_delivery_error',
    ];

    protected $hidden = ['signing_secret'];

    protected $casts = [
        'event_types' => 'array',
        'signing_secret' => 'encrypted',
        'is_active' => 'boolean',
        'last_delivered_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
