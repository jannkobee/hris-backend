<?php

namespace App\Http\Requests;

use App\Rules\TenantRule;
use Illuminate\Foundation\Http\FormRequest;

class AttendanceCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['attendance_id' => ['required', TenantRule::exists('attendances')], 'requested_time_in' => ['nullable', 'date'], 'requested_time_out' => ['nullable', 'date', 'after:requested_time_in'], 'reason' => ['required', 'string', 'max:2000']];
    }
}
