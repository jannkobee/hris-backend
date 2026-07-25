<?php

namespace App\Models;

use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveCreditSetting extends Model
{
    use HasUuids, HasFilterScope;

    public $model_name = 'Leave Credit Setting';

    protected $fillable = [
        'leave_type_id',
        'name',
        'description',
        'credit_amount',
        'frequency',
        'run_months',
        'is_active',
    ];

    protected array $filterable = [
        'leave_type_id',
        'name',
        'frequency',
        'is_active',
    ];

    protected $casts = [
        'credit_amount' => 'decimal:2',
        'run_months' => 'array',
        'is_active' => 'boolean',
    ];

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }
}
