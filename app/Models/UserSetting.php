<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSetting extends \Illuminate\Database\Eloquent\Model
{
    use BelongsToOrganization, HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'setting_key',
        'setting_value',
    ];

    protected $casts = ['setting_value' => 'array'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
