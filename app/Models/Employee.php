<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use BelongsToOrganization, HasFilterScope, HasUuids;

    public $model_name = 'Employee';

    public function save(array $options = [])
    {
        $organization = app(\App\Tenancy\TenantContext::class)->organization();

        return $this->getConnection()->transaction(function () use ($organization, $options) {
            // Serialize capacity decisions for all employee writes in a tenant.
            $lockedOrganization = Organization::query()->whereKey($organization->id)->lockForUpdate()->firstOrFail();
            if ($lockedOrganization->plan_code === 'basic_free') {
                $today = now($lockedOrganization->timezone)->toDateString();
                $persisted = $this->exists ? static::query()->whereKey($this->getKey())->first() : null;
                $wasActive = $persisted && ($persisted->employment_effective_to === null
                    || $persisted->employment_effective_to->toDateString() >= $today);
                $willBeActive = $this->employment_effective_to === null
                    || $this->employment_effective_to->toDateString() >= $today;

                if ($willBeActive && ! $wasActive) {
                    $count = static::query()->where('organization_id', $organization->id)
                        ->where(function ($query) use ($today) {
                            $query->whereNull('employment_effective_to')->orWhereDate('employment_effective_to', '>=', $today);
                        })->count();
                    $limit = app(\App\Services\Plans\PlanEntitlementService::class)->employeeLimit($lockedOrganization);
                    if ($count >= $limit) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'organization' => "Basic includes {$limit} active employees. Upgrade before adding or reactivating another employee.",
                        ]);
                    }
                }
            }

            return parent::save($options);
        });
    }

    protected $fillable = [
        'user_id',
        'manager_id',
        'employee_no',
        'hire_date',
        'employment_effective_from',
        'employment_effective_to',
        'employment_status_id',
        'department_id',
        'position_id',
        'job_grade_id',
        'basic_monthly_salary',
        'pay_schedule',
        'meta',
    ];

    protected array $filterable = [
        'employee_no',
        'user.first_name',
        'user.middle_name',
        'user.last_name',
        'user.email',
    ];

    protected $casts = [
        'hire_date' => 'date:Y-m-d',
        'employment_effective_from' => 'date:Y-m-d',
        'employment_effective_to' => 'date:Y-m-d',
        'basic_monthly_salary' => 'decimal:2',
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'manager_id');
    }

    public function directReports(): HasMany
    {
        return $this->hasMany(self::class, 'manager_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function employmentStatus(): BelongsTo
    {
        return $this->belongsTo(EmploymentStatus::class);
    }

    public function jobGrade(): BelongsTo
    {
        return $this->belongsTo(JobGrade::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(EmployeeAddress::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(EmployeeContact::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    public function payrollItems(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function leaveCredits(): HasMany
    {
        return $this->hasMany(LeaveCredit::class);
    }

    public function shiftAssignments(): HasMany
    {
        return $this->hasMany(ShiftAssignment::class);
    }

    public function expenseClaims(): HasMany
    {
        return $this->hasMany(ExpenseClaim::class);
    }
}
