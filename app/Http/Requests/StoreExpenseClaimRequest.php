<?php

namespace App\Http\Requests;

use App\Rules\TenantRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['employee_id' => ['required', TenantRule::exists('employees')], 'expense_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'], 'category' => ['required', 'string', 'max:100'], 'description' => ['required', 'string', 'max:2000'], 'amount' => ['required', 'numeric', 'min:0.01'], 'receipt_path' => ['nullable', 'string', 'max:500']];
    }
}
