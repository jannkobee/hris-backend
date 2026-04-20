<?php

namespace App\Models;

use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasUuids, HasFilterScope;

    public $model_name = 'Attendance';

    protected $fillable = [
        'employee_id',
        'date',
        'time_in',
        'time_in_notes',
        'time_out',
        'time_out_notes',
        'ip_address'
    ];

    protected array $filterable = [
        'date',
        'time_in_notes',
        'time_out_notes',
        'employee.first_name',
        'employee.last_name',
        'employee.email',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
