<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationDataExport extends Model
{
    use BelongsToOrganization, HasUuids;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_READY = 'ready';

    public const STATUS_FAILED = 'failed';

    public const STATUS_EXPIRED = 'expired';

    protected $fillable = ['requested_by', 'status', 'disk', 'path', 'checksum', 'expires_at', 'error_message'];

    protected $hidden = ['path', 'checksum', 'error_message'];

    protected $casts = ['expires_at' => 'datetime'];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function isDownloadable(): bool
    {
        return $this->status === self::STATUS_READY && $this->expires_at?->isFuture() && $this->path !== null;
    }
}
