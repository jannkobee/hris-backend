<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class LeaveBlackoutDate extends Model
{
    use BelongsToOrganization,HasUuids;

    protected $fillable = ['leave_type_id', 'start_date', 'end_date', 'name', 'reason'];

    protected $casts = ['start_date' => 'date:Y-m-d', 'end_date' => 'date:Y-m-d'];
}
