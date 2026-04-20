<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules()
    {
        return [
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'time_in' => 'required|date_format:H:i',
            'time_in_notes' => 'nullable|string|max:255',
            'time_out' => 'nullable|date_format:H:i',
            'time_out_notes' => 'nullable|string|max:255',
            'ip_address' => 'nullable|ip',
        ];
    }
}
