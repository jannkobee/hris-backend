<?php

namespace App\Http\Requests;

use App\Rules\TenantRule;
use Illuminate\Foundation\Http\FormRequest;

class LeaveCreditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'uuid', TenantRule::exists('employees')],
            'leave_type_id' => ['required', 'uuid', TenantRule::exists('leave_types')],
            'year' => ['required', 'integer', 'between:2000,2100'],
            'total_earned' => ['required', 'numeric', 'min:0', 'max:999.99'],
            'used' => ['nullable', 'numeric', 'min:0', 'lte:total_earned'],
        ];
    }
}
