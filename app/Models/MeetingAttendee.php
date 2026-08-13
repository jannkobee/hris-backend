<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\Pivot;

class MeetingAttendee extends Pivot
{
    use HasUuids;

    public $incrementing = false;

    protected $table = 'meeting_attendees';

    protected $fillable = ['meeting_id', 'user_id', 'is_required', 'response'];

    protected $casts = ['is_required' => 'boolean'];
}
