<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeaveTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $model = $this->route('leaveType');
        $modelId = is_object($model) ? $model->id : $model;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('leave_types', 'name')->ignore($modelId),
            ],
            'description' => ['nullable', 'string'],
        ];
    }
}
