<?php

namespace App\Models;

use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    use HasUuids, HasFilterScope;

    public $model_name = 'Leave Type';

    protected $fillable = [
        'name',
        'default_days',
        'is_paid'
    ];

    protected array $filterable = [
        'name',
        'default_days',
        'is_paid',
    ];

    protected $casts = [
        'is_paid' => 'boolean',
    ];

    public function creditSettings(): HasMany
    {
        return $this->hasMany(LeaveCreditSetting::class);
    }

    public function leaveCredits(): HasMany
    {
        return $this->hasMany(LeaveCredit::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function conversionRequests(): HasMany
    {
        return $this->hasMany(LeaveConversionRequest::class);
    }
}
