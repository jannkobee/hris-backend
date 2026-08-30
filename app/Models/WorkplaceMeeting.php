<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkplaceMeeting extends Model
{
    use BelongsToOrganization, HasUuids;

    protected $fillable = [
        'series_id',
        'room_id',
        'organizer_id',
        'title',
        'type',
        'agenda',
        'minutes',
        'decisions',
        'links',
        'starts_at',
        'ends_at',
        'status',
        'completed_at',
        'cancelled_at',
    ];

    protected $casts = [
        'decisions' => 'array',
        'links' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(MeetingRoom::class, 'room_id');
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function attendees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'meeting_attendees', 'meeting_id', 'user_id')
            ->using(MeetingAttendee::class)
            ->wherePivot('organization_id', app(\App\Tenancy\TenantContext::class)->id())
            ->withPivot(['id', 'organization_id', 'is_required', 'response'])
            ->withTimestamps();
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(MeetingAttachment::class, 'meeting_id');
    }

    public function actionItems(): HasMany
    {
        return $this->hasMany(MeetingActionItem::class, 'meeting_id');
    }
}
