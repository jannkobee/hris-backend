<?php

namespace App\Http\Requests;

use App\Rules\TenantRule;
use Illuminate\Foundation\Http\FormRequest;

class ApprovalDelegationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['delegator_id' => ['required', 'uuid', TenantRule::exists('users'), 'different:delegate_id'], 'delegate_id' => ['required', 'uuid', TenantRule::exists('users')], 'starts_on' => ['required', 'date_format:Y-m-d'], 'ends_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:starts_on'], 'reason' => ['nullable', 'string', 'max:255']];
    }
}
