<?php

namespace App\Http\Requests;

use App\Rules\TenantRule;
use Illuminate\Foundation\Http\FormRequest;

class LeaveConversionRequestRequest extends FormRequest
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
            'credits_requested' => ['required', 'numeric', 'min:0.5'],
            'monetary_value' => ['nullable', 'numeric', 'min:0'],
            'reason' => ['nullable', 'string'],
        ];
    }
}
