<?php

namespace App\Models;

use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasUuids, HasFilterScope;

    protected $fillable = [
        'user_id',
        'date',
        'time_in',
        'time_in_notes',
        'time_out',
        'time_out_notes',
        'ip_address'
    ];

    protected array $filterable = [
        'date',
        'time_in_notes',
        'time_out_notes',
        'user.first_name',
        'user.last_name',
        'user.email',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
