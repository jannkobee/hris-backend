<?php

namespace App\Http\Requests;

use App\Models\Holiday;
use App\Rules\TenantRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HolidayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $holiday = $this->route('holiday');
        $holidayId = is_object($holiday) ? $holiday->id : $holiday;

        return [
            'name' => ['required', 'string', 'max:150'],
            'date' => [
                'required',
                'date_format:Y-m-d',
                TenantRule::unique('holidays', 'date')->ignore($holidayId),
            ],
            'type' => ['required', Rule::in(Holiday::TYPES)],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'date.unique' => 'This date already has a workforce calendar entry.',
            'type.in' => 'Select a valid workforce calendar day type.',
        ];
    }
}
