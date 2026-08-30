<?php

namespace App\Models;

use App\Traits\HasFilterScope;
use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveCreditSetting extends Model
{
    use BelongsToOrganization, HasFilterScope, HasUuids;

    public $model_name = 'Leave Credit Setting';

    protected $fillable = [
        'leave_type_id',
        'name',
        'description',
        'credit_amount',
        'frequency',
        'run_months',
        'eligible_employment_status_ids',
        'eligible_department_ids',
        'eligible_position_ids',
        'eligible_job_grade_ids',
        'minimum_service_months',
        'grant_on_hire',
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
        'eligible_employment_status_ids' => 'array',
        'eligible_department_ids' => 'array',
        'eligible_position_ids' => 'array',
        'eligible_job_grade_ids' => 'array',
        'minimum_service_months' => 'integer',
        'grant_on_hire' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }
}
