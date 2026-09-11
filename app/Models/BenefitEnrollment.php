<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BenefitEnrollment extends Model
{
    use BelongsToOrganization, HasFactory, HasUuids;

    protected $fillable = ['benefit_plan_id', 'employee_id', 'effective_from', 'effective_to', 'status'];

    protected $casts = ['effective_from' => 'date:Y-m-d', 'effective_to' => 'date:Y-m-d'];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(BenefitPlan::class, 'benefit_plan_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
