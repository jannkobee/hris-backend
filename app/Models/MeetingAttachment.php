<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingAttachment extends Model
{
    use HasUuids;

    protected $fillable = [
        'meeting_id',
        'uploaded_by',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
        'description',
    ];

    protected $hidden = ['disk', 'path'];

    protected $casts = ['size' => 'integer'];

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(WorkplaceMeeting::class, 'meeting_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
