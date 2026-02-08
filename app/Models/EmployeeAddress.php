<?php

namespace App\Models;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class EmployeeAddress extends Model
{
    use HasUuids;

    protected $fillable = [
        'employee_id',
        'type',
        'address_line_1',
        'address_line_2',
        'city',
        'province',
        'postal_code',
        'country',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
