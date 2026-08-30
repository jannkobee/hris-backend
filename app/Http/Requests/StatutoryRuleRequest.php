<?php

namespace App\Http\Requests;

use App\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StatutoryRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $countryCode = app(TenantContext::class)->organization()->country_code ?: 'PH';
        $this->merge(['country_code' => strtoupper((string) $countryCode)]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'country_code' => ['required', 'string', 'size:2'],
            'effective_from' => ['required', 'date'],
            'effective_until' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'rules' => ['required', 'array', 'min:1'],
            'rules.*' => ['numeric', 'min:0'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $allowed = [
                'payroll.sss_employee_rate', 'payroll.sss_employer_rate', 'payroll.sss_min_msc',
                'payroll.sss_max_msc', 'payroll.sss_ec_low', 'payroll.sss_ec_high',
                'payroll.sss_ec_threshold', 'payroll.philhealth_rate', 'payroll.philhealth_salary_floor',
                'payroll.philhealth_salary_ceiling', 'payroll.pagibig_employee_rate_low',
                'payroll.pagibig_employee_rate', 'payroll.pagibig_employer_rate',
                'payroll.pagibig_rate_threshold', 'payroll.pagibig_max_salary',
            ];
            if (array_diff(array_keys($this->input('rules', [])), $allowed) !== []) {
                $validator->errors()->add('rules', 'Only statutory contribution rule keys can be overridden.');
            }
        }];
    }
}
