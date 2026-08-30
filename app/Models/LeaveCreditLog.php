<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveCreditLog extends Model
{
    use BelongsToOrganization, HasUuids;

    public $model_name = 'Leave Credit Log';

    protected $fillable = [
        'leave_credit_setting_id',
        'employee_id',
        'leave_type_id',
        'year',
        'month',
        'credited_amount',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'credited_amount' => 'decimal:2',
    ];

    public function leaveCreditSetting(): BelongsTo
    {
        return $this->belongsTo(LeaveCreditSetting::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }
}
