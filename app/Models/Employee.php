<?php

namespace App\Models;

use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasUuids, HasFilterScope;

    protected $fillable = [
        'user_id',
        'employee_no',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'birthdate',
        'gender',
        'hire_date',
        'employment_status_id',
        'department_id',
        'position_id',
        'job_grade_id',
        'meta'
    ];

    protected array $filterable = [
        'employee_no',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'birthdate',
    ];

    protected $casts = [
        'birthdate' => 'date',
        'hire_date' => 'date',
        'meta' => 'array'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

    public function addresses(): HasMany
    {
        return $this->hasMany(EmployeeAddress::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(EmployeeContact::class);
    }
}
