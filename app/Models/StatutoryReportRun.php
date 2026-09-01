<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatutoryReportRun extends Model
{
    use BelongsToOrganization, HasFactory, HasUuids;

    protected $fillable = ['payroll_period_id', 'report_type', 'status', 'snapshot', 'generated_by', 'generated_at'];

    protected $casts = ['snapshot' => 'array', 'generated_at' => 'datetime'];
}
