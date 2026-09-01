<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerformanceGoal extends Model
{
    use BelongsToOrganization, HasFactory, HasUuids;

    protected $fillable = ['employee_id', 'owner_id', 'title', 'description', 'due_date', 'progress', 'status'];

    protected $casts = ['due_date' => 'date:Y-m-d', 'progress' => 'integer'];
}
