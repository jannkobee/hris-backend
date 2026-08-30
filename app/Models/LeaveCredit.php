<?php

namespace App\Models;

use App\Traits\HasFilterScope;
use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveCredit extends Model
{
    use BelongsToOrganization, HasUuids, HasFilterScope;

    public $model_name = 'Leave Credit';

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'year',
        'total_earned',
        'used'
    ];

    protected array $filterable = [
        'employee_id',
        'leave_type_id',
        'year',
    ];

    protected $appends = ['remaining'];

    protected $casts = [
        'year' => 'integer',
        'total_earned' => 'decimal:2',
        'used' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function conversionRequests(): HasMany
    {
        return $this->hasMany(LeaveConversionRequest::class);
    }

    public function getRemainingAttribute(): float
    {
        return (float) $this->total_earned - (float) $this->used;
    }
}
