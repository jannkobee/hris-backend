<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasFilterScope;
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
        'initial_credit_amount',
        'is_active',
        'allow_negative_balance', 'negative_balance_limit', 'carry_over_limit', 'carry_over_expiry_month',
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
        'initial_credit_amount' => 'decimal:2',
        'is_active' => 'boolean',
        'allow_negative_balance' => 'boolean', 'negative_balance_limit' => 'decimal:2', 'carry_over_limit' => 'decimal:2', 'carry_over_expiry_month' => 'integer',
    ];

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }
}
