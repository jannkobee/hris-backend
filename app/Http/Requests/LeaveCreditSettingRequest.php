<?php

namespace App\Http\Requests;

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
            'leave_type_id' => ['required', 'uuid', 'exists:leave_types,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'credit_amount' => ['required', 'numeric', 'min:0'],
            'frequency' => ['nullable', 'string', 'max:50'], // e.g. monthly, quarterly, semi_annually, annually, custom — label only
            'run_months' => ['required', 'array', 'min:1'],
            'run_months.*' => ['integer', 'between:1,12'],
            'is_active' => ['boolean'],
        ];
    }
}
