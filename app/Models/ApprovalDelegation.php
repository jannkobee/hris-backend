<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ApprovalDelegation extends Model
{
    use BelongsToOrganization,HasUuids;

    protected $fillable = ['delegator_id', 'delegate_id', 'starts_on', 'ends_on', 'reason'];

    protected $casts = ['starts_on' => 'date:Y-m-d', 'ends_on' => 'date:Y-m-d'];

    public function delegator()
    {
        return $this->belongsTo(User::class, 'delegator_id');
    }

    public function delegate()
    {
        return $this->belongsTo(User::class, 'delegate_id');
    }

    public function activeFor(string $userId): bool
    {
        return $this->delegator_id === $userId && now()->betweenIncluded($this->starts_on->startOfDay(), $this->ends_on->endOfDay());
    }
}
