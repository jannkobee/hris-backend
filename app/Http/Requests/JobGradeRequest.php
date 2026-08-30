<?php

namespace App\Http\Requests;

use App\Rules\TenantRule;
use Illuminate\Foundation\Http\FormRequest;

class JobGradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $model = $this->route('jobGrade');
        $modelId = is_object($model) ? $model->id : $model;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                TenantRule::unique('job_grades', 'name')->ignore($modelId),
            ],
            'description' => ['nullable', 'string'],
        ];
    }
}
