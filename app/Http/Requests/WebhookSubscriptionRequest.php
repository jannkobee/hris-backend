<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WebhookSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'url' => ['required', 'url', 'max:2048', 'regex:/^https:\/\//i'],
            'event_types' => ['required', 'array', 'min:1'],
            'event_types.*' => [Rule::in([
                'employee.created', 'employee.updated', 'leave.approved',
                'overtime.approved', 'payroll.locked', 'attendance.corrected',
            ])],
            'is_active' => ['boolean'],
        ];
    }
}
