<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LeaveTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:leave_types,name,' . $this->route('leave_type')],
            'default_days' => ['required', 'numeric', 'min:0', 'max:365'],
            'is_paid' => ['required', 'boolean'],
        ];
    }
}
