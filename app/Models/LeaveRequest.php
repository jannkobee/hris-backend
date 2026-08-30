<?php

namespace App\Models;

use App\Casts\TimeOfDay;
use App\Traits\HasFilterScope;
use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveRequest extends Model
{
    use BelongsToOrganization, HasFilterScope, HasUuids;

    public $model_name = 'Leave Request';

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'start_date',
        'start_time',
        'end_date',
        'end_time',
        'reason',
        'status',
        'approved_by',
        'approved_at',
        'remarks',
    ];

    protected array $filterable = [
        'employee_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'status',
    ];

    protected $appends = [
        'start_at',
        'end_at',
    ];

    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'start_time' => TimeOfDay::class,
        'end_date' => 'date:Y-m-d',
        'end_time' => TimeOfDay::class,
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

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(LeaveRequestAttachment::class);
    }

    public function getStartAtAttribute(): ?string
    {
        if (! $this->start_date) {
            return null;
        }

        return $this->start_date->format('Y-m-d').'T'.substr($this->start_time ?? '00:00', 0, 5);
    }

    public function getEndAtAttribute(): ?string
    {
        if (! $this->end_date) {
            return null;
        }

        return $this->end_date->format('Y-m-d').'T'.substr($this->end_time ?? '00:00', 0, 5);
    }
}
