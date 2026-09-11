<?php

namespace App\Http\Requests;

use App\Rules\TenantRule;
use Illuminate\Foundation\Http\FormRequest;

class LeaveCreditForecastRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'uuid', TenantRule::exists('employees')],
            'target_date' => ['required', 'date', 'after:today', 'before:+3 years'],
            'leave_type_id' => ['nullable', 'uuid', TenantRule::exists('leave_types')],
        ];
    }
}

