<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlatformPricingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'minimum_billable_employees' => ['sometimes', 'integer', 'min:0', 'max:10000'],
            'effective_at' => ['nullable', 'date', 'after_or_equal:now'],
            'free_employee_limit' => ['required', 'integer', 'min:0', 'max:10000'],
            'growth_price_per_employee' => ['required', 'integer', 'min:0', 'max:1000000'],
            'currency' => ['required', 'in:php'],
        ];
    }
}
