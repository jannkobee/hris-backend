<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingEnrollment extends Model
{
    use BelongsToOrganization, HasFactory, HasUuids;

    protected $fillable = ['course_id', 'employee_id', 'status', 'completed_on', 'certificate_expires_on'];

    protected $casts = ['completed_on' => 'date:Y-m-d', 'certificate_expires_on' => 'date:Y-m-d'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
