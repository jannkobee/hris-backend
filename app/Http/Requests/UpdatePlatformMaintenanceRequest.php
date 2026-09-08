<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlatformMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'enabled' => ['required', 'boolean'],
            'retry_after' => ['nullable', 'integer', 'min:60', 'max:3600'],
            'reason' => ['nullable', 'string', 'max:500', 'required_if:enabled,true'],
        ];
    }
}
