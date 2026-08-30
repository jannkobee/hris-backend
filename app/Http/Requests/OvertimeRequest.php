<?php

namespace App\Http\Requests;

use App\Rules\TenantRule;
use Illuminate\Foundation\Http\FormRequest;

class OvertimeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Adjust to your actual permission/policy check.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'employee_id' => ['required', TenantRule::exists('employees')],
            'date' => ['required', 'date'],
            'time_start' => ['required', 'date_format:H:i'],
            'time_end' => ['required', 'date_format:H:i', 'after:time_start'],
            'hours' => ['required', 'numeric', 'min:0.01', 'max:24'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'status' => ['prohibited'],
        ];
    }

    /**
     * Custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'time_end.after' => 'The end time must be after the start time.',
        ];
    }
}
