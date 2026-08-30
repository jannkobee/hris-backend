<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShiftTemplate extends Model
{
    use BelongsToOrganization, HasFilterScope, HasUuids;

    public $model_name = 'Shift template';

    protected $fillable = [
        'name', 'code', 'start_time', 'end_time', 'break_minutes', 'grace_minutes', 'days_of_week', 'is_active',
    ];

    protected $casts = [
        'days_of_week' => 'array',
        'is_active' => 'boolean',
        'break_minutes' => 'integer',
        'grace_minutes' => 'integer',
    ];

    protected array $filterable = ['name', 'code', 'is_active'];

    public function assignments(): HasMany
    {
        return $this->hasMany(ShiftAssignment::class);
    }
}
