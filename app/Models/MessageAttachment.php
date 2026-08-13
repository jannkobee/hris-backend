<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageAttachment extends Model
{
    use HasUuids;

    protected $fillable = [
        'conversation_id',
        'message_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
    ];

    protected $hidden = ['disk', 'path'];

    protected $casts = ['size' => 'integer'];

    protected $appends = ['download_url', 'preview_url', 'is_image'];

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function getDownloadUrlAttribute(): string
    {
        return "/conversations/{$this->conversation_id}/attachments/{$this->id}";
    }

    public function getPreviewUrlAttribute(): ?string
    {
        return $this->is_image ? $this->download_url.'?inline=1' : null;
    }

    public function getIsImageAttribute(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'image/');
    }
}
