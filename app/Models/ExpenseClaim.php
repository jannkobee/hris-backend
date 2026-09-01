<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseClaim extends Model
{
    use BelongsToOrganization, HasFactory, HasUuids;

    protected $fillable = ['employee_id', 'expense_date', 'category', 'description', 'receipt_path', 'amount', 'status', 'reviewed_by', 'reviewed_at', 'reviewer_note', 'reimbursed_by', 'reimbursed_at', 'payment_reference'];

    protected $casts = ['expense_date' => 'date:Y-m-d', 'amount' => 'decimal:2', 'reviewed_at' => 'datetime', 'reimbursed_at' => 'datetime'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
