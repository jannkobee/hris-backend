<?php

namespace App\Models\Pivots;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ConversationParticipant extends Pivot
{
    use BelongsToOrganization, HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $casts = [
        'last_read_at' => 'datetime',
    ];
}
