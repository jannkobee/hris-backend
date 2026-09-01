<?php

namespace App\Http\Requests;

use App\Rules\TenantRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePerformanceReviewRequest extends FormRequest
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
        return ['employee_id' => ['required', TenantRule::exists('employees')], 'cycle_name' => ['required', 'string', 'max:255'], 'rating' => ['nullable', 'integer', 'min:1', 'max:5'], 'feedback' => ['nullable', 'string', 'max:5000'], 'status' => ['sometimes', 'in:draft,submitted,finalized']];
    }
}
