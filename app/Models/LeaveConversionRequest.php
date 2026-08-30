<?php

namespace App\Models;

use App\Traits\HasFilterScope;
use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveConversionRequest extends Model
{
    use BelongsToOrganization, HasUuids, HasFilterScope;

    public $model_name = 'Leave Conversion Request';

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'leave_credit_id',
        'credits_requested',
        'monetary_value',
        'reason',
        'status',
        'approved_by',
        'approved_at',
        'remarks',
    ];

    protected array $filterable = [
        'employee_id',
        'leave_type_id',
        'leave_credit_id',
        'status',
    ];

    protected $casts = [
        'credits_requested' => 'decimal:2',
        'monetary_value' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function leaveCredit(): BelongsTo
    {
        return $this->belongsTo(LeaveCredit::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
