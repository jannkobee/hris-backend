<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class LeaveCreditCarryover extends Model
{
    use BelongsToOrganization, HasUuids;

    protected $fillable = ['leave_credit_id', 'target_leave_credit_id', 'amount', 'expires_on', 'expired_at'];

    protected $casts = ['amount' => 'decimal:2', 'expires_on' => 'date:Y-m-d', 'expired_at' => 'datetime'];
}
