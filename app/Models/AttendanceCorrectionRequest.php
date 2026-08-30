<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceCorrectionRequest extends Model
{
    use BelongsToOrganization, HasUuids;

    public const PENDING = 'pending';

    public const APPROVED = 'approved';

    public const REJECTED = 'rejected';

    protected $fillable = ['attendance_id', 'employee_id', 'requested_time_in', 'requested_time_out', 'reason', 'status', 'reviewed_by', 'reviewed_at', 'reviewer_notes'];

    protected $casts = ['requested_time_in' => 'datetime', 'requested_time_out' => 'datetime', 'reviewed_at' => 'datetime'];

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
