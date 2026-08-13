<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequestAttachment extends Model
{
    use HasUuids;

    protected $fillable = [
        'leave_request_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
    ];

    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class);
    }
}
