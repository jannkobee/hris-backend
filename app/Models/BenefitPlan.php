<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BenefitPlan extends Model
{
    use BelongsToOrganization, HasFactory, HasUuids;

    protected $fillable = ['name', 'description', 'employee_contribution', 'employer_contribution', 'is_active'];

    protected $casts = ['employee_contribution' => 'decimal:2', 'employer_contribution' => 'decimal:2', 'is_active' => 'boolean'];

    public function enrollments(): HasMany
    {
        return $this->hasMany(BenefitEnrollment::class);
    }
}
