<?php

namespace App\Http\Requests;

use App\Rules\TenantRule;
use Illuminate\Foundation\Http\FormRequest;

class ShiftAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $assignment = $this->route('shift_assignment');
        $assignmentId = is_object($assignment) ? $assignment->id : $assignment;

        return [
            'employee_id' => ['required', TenantRule::exists('employees')],
            'shift_template_id' => ['required', TenantRule::exists('shift_templates')],
            'work_date' => ['required', 'date_format:Y-m-d', TenantRule::unique('shift_assignments', 'work_date')->ignore($assignmentId)->where('employee_id', $this->input('employee_id'))],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
