<?php

namespace App\Http\Requests;

use App\Rules\TenantRule;
use Illuminate\Foundation\Http\FormRequest;

class EmploymentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $model = $this->route('employmentStatus');
        $modelId = is_object($model) ? $model->id : $model;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                TenantRule::unique('employment_statuses', 'name')->ignore($modelId),
            ],
            'description' => ['nullable', 'string'],
        ];
    }
}
