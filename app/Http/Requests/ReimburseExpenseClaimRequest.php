<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReimburseExpenseClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['payment_reference' => ['required', 'string', 'max:255'], 'reimbursed_at' => ['nullable', 'date']];
    }
}
