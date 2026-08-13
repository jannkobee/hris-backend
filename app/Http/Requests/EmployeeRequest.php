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
            'employee_no' => 'required|string|unique:employees,employee_no,'.$id.',id',
            'hire_date' => 'nullable|date|date_format:Y-m-d',

            'employment_status_id' => 'nullable|exists:employment_statuses,id',
            'department_id' => 'nullable|exists:departments,id',
            'position_id' => 'nullable|exists:positions,id',
            'job_grade_id' => 'nullable|exists:job_grades,id',
            'basic_monthly_salary' => 'nullable|numeric|min:0|max:9999999999.99',
            'pay_schedule' => 'nullable|in:monthly,semi_monthly',

            'meta' => 'nullable|array',

            // Addresses validation
            'addresses' => 'nullable|array',
            'addresses.*.id' => 'nullable|exists:employee_addresses,id',
            'addresses.*.type' => 'required|string|in:current,permanent,previous',
            'addresses.*.address_line_1' => 'required|string|max:255',
            'addresses.*.address_line_2' => 'nullable|string|max:255',
            'addresses.*.city' => 'required|string|max:100',
            'addresses.*.province' => 'required|string|max:100',
            'addresses.*.postal_code' => 'nullable|string|max:20',
            'addresses.*.country' => 'required|string|max:100',

            // Contacts validation
            'contacts' => 'nullable|array',
            'contacts.*.id' => 'nullable|exists:employee_contacts,id',
            'contacts.*.type' => 'required|string|in:mobile,home,work,email,emergency',
            'contacts.*.value' => 'required|string|max:255',
        ];
    }
}
