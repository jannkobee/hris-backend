<?php

namespace App\Models;

use App\Traits\HasFilterScope;
use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    use BelongsToOrganization, HasUuids, HasFilterScope;

    public $model_name = 'Announcement';

    protected $fillable = [
        'title',
        'content',
        'published_at',
        'is_active',
        'created_by'
    ];

    protected array $filterable = [
        'title',
        'content',
        'published_at',
        'is_active'
    ];

    protected $casts = [
        'published_at' => 'date:Y-m-d',
        'is_active' => 'boolean'
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
