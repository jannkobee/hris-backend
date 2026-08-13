<?php

namespace App\Models;

use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollPeriod extends Model
{
    use HasFilterScope, HasUuids;

    protected $fillable = [
        'name',
        'date_from',
        'date_to',
        'payout_date',
        'frequency',
        'status',
        'created_by',
        'approved_by',
        'processed_at',
        'approved_at',
        'paid_at',
        'total_gross',
        'total_deductions',
        'total_net',
    ];

    protected array $filterable = ['name', 'status', 'frequency'];

    protected $casts = [
        'date_from' => 'date:Y-m-d',
        'date_to' => 'date:Y-m-d',
        'payout_date' => 'date:Y-m-d',
        'processed_at' => 'datetime',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
        'total_gross' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'total_net' => 'decimal:2',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
