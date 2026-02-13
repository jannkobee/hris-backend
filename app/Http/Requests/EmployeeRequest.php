<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmployeeRequest extends FormRequest
{
    /**
     * Determine if the Employee is authorized to make this request.
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
        $id = $this->route('employee');

        return [
            'user_id' => 'required|exists:users,id',
            'employee_no' => 'required|string|unique:employees,employee_no,' . $id . ',id',
            'hire_date' => 'nullable|date|date_format:Y-m-d',

            'employment_status_id' => 'nullable|exists:employment_statuses,id',
            'department_id' => 'nullable|exists:departments,id',
            'position_id' => 'nullable|exists:positions,id',
            'job_grade_id' => 'nullable|exists:job_grades,id',

            'meta' => 'nullable|array',
        ];
    }
}
