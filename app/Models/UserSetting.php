<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSetting extends \Illuminate\Database\Eloquent\Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'setting_key',
        'setting_value'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
