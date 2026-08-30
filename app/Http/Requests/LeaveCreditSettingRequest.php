<?php

namespace App\Http\Requests;

use App\Rules\TenantRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
            'initial_credit_amount' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'is_active' => ['boolean'],
            'allow_negative_balance' => ['boolean'],
            'negative_balance_limit' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'carry_over_limit' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'carry_over_expiry_month' => ['nullable', 'integer', 'between:1,12'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->boolean('grant_on_hire') && (float) $this->input('initial_credit_amount', 0) <= 0) {
                $validator->errors()->add(
                    'initial_credit_amount',
                    'Enter an initial credit amount when Grant Initial Credit on Hire is enabled.'
                );
            }
        });
    }
}
