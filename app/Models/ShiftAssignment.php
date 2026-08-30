<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftAssignment extends Model
{
    use BelongsToOrganization, HasFilterScope, HasUuids;

    public $model_name = 'Shift assignment';

    protected $fillable = [
        'employee_id', 'shift_template_id', 'work_date', 'shift_name', 'start_time', 'end_time',
        'break_minutes', 'grace_minutes', 'notes',
    ];

    protected $casts = [
        'work_date' => 'date:Y-m-d',
        'break_minutes' => 'integer',
        'grace_minutes' => 'integer',
    ];

    protected array $filterable = ['work_date', 'shift_name', 'employee.first_name', 'employee.last_name'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function shiftTemplate(): BelongsTo
    {
        return $this->belongsTo(ShiftTemplate::class);
    }
}
