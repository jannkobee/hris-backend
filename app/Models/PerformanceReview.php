<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerformanceReview extends Model
{
    use BelongsToOrganization, HasFactory, HasUuids;

    protected $fillable = ['employee_id', 'reviewer_id', 'cycle_name', 'rating', 'feedback', 'status'];
}
