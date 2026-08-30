<?php

namespace App\Http\Requests;

use App\Rules\TenantRule;
use Illuminate\Foundation\Http\FormRequest;

class LeaveCreditSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'leave_type_id' => ['required', 'uuid', TenantRule::exists('leave_types')],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'credit_amount' => ['required', 'numeric', 'min:0'],
            'frequency' => ['nullable', 'string', 'max:50'], // e.g. monthly, quarterly, semi_annually, annually, custom — label only
            'run_months' => ['required', 'array', 'min:1'],
            'run_months.*' => ['integer', 'between:1,12'],
            'eligible_employment_status_ids' => ['nullable', 'array'],
            'eligible_employment_status_ids.*' => ['uuid', TenantRule::exists('employment_statuses')],
            'eligible_department_ids' => ['nullable', 'array'],
            'eligible_department_ids.*' => ['uuid', TenantRule::exists('departments')],
            'eligible_position_ids' => ['nullable', 'array'],
            'eligible_position_ids.*' => ['uuid', TenantRule::exists('positions')],
            'eligible_job_grade_ids' => ['nullable', 'array'],
            'eligible_job_grade_ids.*' => ['uuid', TenantRule::exists('job_grades')],
            'minimum_service_months' => ['nullable', 'integer', 'min:0', 'max:600'],
            'grant_on_hire' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }
}
