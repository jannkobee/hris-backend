<?php

namespace App\Http\Requests;

use App\Rules\TenantRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePerformanceGoalRequest extends FormRequest
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
        return ['employee_id' => ['required', TenantRule::exists('employees')], 'title' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string'], 'due_date' => ['nullable', 'date_format:Y-m-d'], 'progress' => ['sometimes', 'integer', 'min:0', 'max:100']];
    }
}
