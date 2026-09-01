<?php

namespace App\Http\Requests;

use App\Rules\TenantRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePayrollAdjustmentRunRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return ['payroll_period_id' => ['required', TenantRule::exists('payroll_periods')], 'name' => ['required', 'string', 'max:255'], 'reason' => ['required', 'string', 'max:5000']];
    }
}
