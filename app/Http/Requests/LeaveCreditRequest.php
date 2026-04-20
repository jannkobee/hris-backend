<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeaveCreditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $model = $this->route('leaveCredit');
        $modelId = is_object($model) ? $model->id : $model;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('leave_credits', 'name')->ignore($modelId),
            ],
            'description' => ['nullable', 'string'],
        ];
    }
}
