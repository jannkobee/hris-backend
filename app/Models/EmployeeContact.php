<?php

namespace App\Models;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class EmployeeContact extends Model
{
    use HasUuids;

    protected $fillable = [
        'employee_id',
        'type',
        'value',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
