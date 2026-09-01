<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollAdjustmentRun extends Model
{
    use BelongsToOrganization, HasFactory, HasUuids;

    protected $fillable = ['payroll_period_id', 'name', 'status', 'reason', 'created_by', 'locked_at', 'locked_by'];

    protected $casts = ['locked_at' => 'datetime'];

    public function period(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollAdjustmentItem::class, 'adjustment_run_id');
    }

    public function isLocked(): bool
    {
        return $this->locked_at !== null;
    }
}
