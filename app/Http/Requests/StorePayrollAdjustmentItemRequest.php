<?php

namespace App\Http\Requests;

use App\Rules\TenantRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePayrollAdjustmentItemRequest extends FormRequest
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
        return ['employee_id' => ['required', TenantRule::exists('employees')], 'type' => ['required', 'in:earning,deduction'], 'amount' => ['required', 'numeric', 'min:0.01'], 'description' => ['required', 'string', 'max:500']];
    }
}
