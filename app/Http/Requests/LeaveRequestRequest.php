<?php

namespace App\Http\Requests;

use App\Rules\TenantRule;
use App\Services\AppSettings\AppSettingService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeaveRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $attachmentsEnabled = app(AppSettingService::class)->get('leave.attachments_enabled', true);

        return [
            'employee_id' => [$this->isMethod('post') ? 'required' : 'sometimes', 'uuid', TenantRule::exists('employees')],
            'leave_type_id' => [$this->isMethod('post') ? 'required' : 'sometimes', 'uuid', TenantRule::exists('leave_types')],
            'start_at' => [$this->isMethod('post') ? 'required' : 'sometimes', 'date'],
            'end_at' => [$this->isMethod('post') ? 'required' : 'sometimes', 'date', 'after_or_equal:start_at'],
            'reason' => [$this->isMethod('post') ? 'required' : 'sometimes', 'string', 'max:500'],
            'status' => ['prohibited'],
            'attachments' => [Rule::when($attachmentsEnabled, ['nullable', 'array', 'max:5'], ['prohibited'])],
            'attachments.*' => [Rule::when($attachmentsEnabled, ['file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'], ['prohibited'])],
        ];
    }
}
