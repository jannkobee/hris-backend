<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SavedReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'report_type' => ['required', Rule::in(['attendance_summary', 'leave_summary', 'overtime_summary', 'payroll_register', 'workforce_cost_summary'])],
            'filters' => ['nullable', 'array'],
            'filters.from' => ['required_with:filters', 'date'],
            'filters.to' => ['required_with:filters', 'date', 'after_or_equal:filters.from'],
            'delivery_frequency' => ['nullable', Rule::in(['daily', 'weekly', 'monthly'])],
            'delivery_period_days' => ['required_with:delivery_frequency', 'integer', 'min:1', 'max:366'],
            'delivery_recipients' => ['required_with:delivery_frequency', 'array', 'max:20'],
            'delivery_recipients.*' => ['required', 'email:rfc', 'distinct'],
        ];
    }
}
