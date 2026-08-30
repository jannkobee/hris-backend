<?php

namespace App\Http\Requests;

use App\Rules\TenantRule;
use Illuminate\Foundation\Http\FormRequest;

class ShiftTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $template = $this->route('shift_template');
        $templateId = is_object($template) ? $template->id : $template;

        return [
            'name' => ['required', 'string', 'max:100', TenantRule::unique('shift_templates', 'name')->ignore($templateId)],
            'code' => ['nullable', 'string', 'max:40', TenantRule::unique('shift_templates', 'code')->ignore($templateId)],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'different:start_time'],
            'break_minutes' => ['nullable', 'integer', 'min:0', 'max:720'],
            'grace_minutes' => ['nullable', 'integer', 'min:0', 'max:240'],
            'days_of_week' => ['nullable', 'array'],
            'days_of_week.*' => ['integer', 'between:0,6', 'distinct'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
