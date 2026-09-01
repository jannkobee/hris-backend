<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollAdjustmentItem extends Model
{
    use BelongsToOrganization, HasFactory, HasUuids;

    protected $fillable = ['employee_id', 'type', 'amount', 'description'];

    protected $casts = ['amount' => 'decimal:2'];

    public function run(): BelongsTo
    {
        return $this->belongsTo(PayrollAdjustmentRun::class, 'adjustment_run_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
