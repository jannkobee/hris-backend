<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeLifecycleTask extends Model
{
    use BelongsToOrganization, HasFactory, HasUuids;

    protected $fillable = ['checklist_id', 'employee_id', 'owner_id', 'title', 'description', 'due_date', 'completed_at', 'completed_by'];

    protected $casts = ['due_date' => 'date:Y-m-d', 'completed_at' => 'datetime'];

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(EmployeeLifecycleChecklist::class, 'checklist_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
