<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MeetingRoom extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'code',
        'location',
        'floor',
        'capacity',
        'amenities',
        'description',
        'status',
        'created_by',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'amenities' => 'array',
    ];

    public function meetings(): HasMany
    {
        return $this->hasMany(WorkplaceMeeting::class, 'room_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
