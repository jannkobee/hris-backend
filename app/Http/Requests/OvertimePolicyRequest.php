<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OvertimePolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['day_type' => ['required', 'string', Rule::in(['regular_day', 'rest_day', 'regular_holiday', 'special_non_working_day', 'special_working_day', 'company_holiday'])], 'multiplier' => ['required', 'numeric', 'min:1', 'max:10'], 'is_active' => ['boolean']];
    }
}
