<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlatformHealthSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'failed_jobs_warning' => ['required', 'integer', 'min:1', 'max:100000'],
            'failed_jobs_critical' => ['required', 'integer', 'gte:failed_jobs_warning', 'max:100000'],
            'snapshot_interval_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
        ];
    }
}
