<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BenefitEnrollment extends Model
{
    use BelongsToOrganization, HasFactory, HasUuids;

    protected $fillable = ['benefit_plan_id', 'employee_id', 'effective_from', 'effective_to', 'status'];

    protected $casts = ['effective_from' => 'date:Y-m-d', 'effective_to' => 'date:Y-m-d'];
}
