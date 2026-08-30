<?php

namespace App\Http\Requests;

use App\Rules\TenantRule;
use Illuminate\Foundation\Http\FormRequest;

class LeaveBlackoutDateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['leave_type_id' => ['nullable', 'uuid', TenantRule::exists('leave_types')], 'name' => ['required', 'string', 'max:150'], 'reason' => ['nullable', 'string', 'max:255'], 'start_date' => ['required', 'date_format:Y-m-d'], 'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date']];
    }
}
