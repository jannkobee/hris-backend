<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'payroll_period_id',
        'employee_id',
        'scheduled_days',
        'days_worked',
        'paid_leave_days',
        'unpaid_leave_days',
        'absent_days',
        'late_minutes',
        'undertime_minutes',
        'basic_pay',
        'overtime_pay',
        'allowances',
        'other_earnings',
        'absence_deduction',
        'late_undertime_deduction',
        'unpaid_leave_deduction',
        'gross_pay',
        'sss_employee',
        'sss_employer',
        'sss_ec_employer',
        'philhealth_employee',
        'philhealth_employer',
        'pagibig_employee',
        'pagibig_employer',
        'withholding_tax',
        'other_deductions',
        'total_deductions',
        'net_pay',
        'notes',
        'exceptions',
        'exceptions_acknowledged_at',
        'exceptions_acknowledged_by',
        'calculation_snapshot',
    ];

    protected $casts = [
        'scheduled_days' => 'decimal:2',
        'days_worked' => 'decimal:2',
        'paid_leave_days' => 'decimal:2',
        'unpaid_leave_days' => 'decimal:2',
        'absent_days' => 'decimal:2',
        'late_minutes' => 'integer',
        'undertime_minutes' => 'integer',
        'basic_pay' => 'decimal:2',
        'overtime_pay' => 'decimal:2',
        'allowances' => 'decimal:2',
        'other_earnings' => 'decimal:2',
        'absence_deduction' => 'decimal:2',
        'late_undertime_deduction' => 'decimal:2',
        'unpaid_leave_deduction' => 'decimal:2',
        'gross_pay' => 'decimal:2',
        'sss_employee' => 'decimal:2',
        'sss_employer' => 'decimal:2',
        'sss_ec_employer' => 'decimal:2',
        'philhealth_employee' => 'decimal:2',
        'philhealth_employer' => 'decimal:2',
        'pagibig_employee' => 'decimal:2',
        'pagibig_employer' => 'decimal:2',
        'withholding_tax' => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_pay' => 'decimal:2',
        'exceptions' => 'array',
        'exceptions_acknowledged_at' => 'datetime',
        'calculation_snapshot' => 'array',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function exceptionAcknowledger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'exceptions_acknowledged_by');
    }
}
