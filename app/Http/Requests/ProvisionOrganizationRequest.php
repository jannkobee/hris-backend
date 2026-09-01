<?php

namespace App\Http\Requests;

use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProvisionOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'slug' => ['required', 'string', 'regex:/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/'],
            'name' => ['required', 'string', 'max:255'],
            'timezone' => ['required', 'timezone'],
            'country_code' => ['required', 'string', 'size:2', 'alpha'],
            'plan_code' => ['required', 'string', Rule::in(array_keys(config('plans.plans')))],
            'subscription_status' => ['nullable', Rule::in(Organization::SUBSCRIPTION_STATUSES)],
            'trial_ends_at' => ['nullable', 'date', 'after:now'],
            'current_period_ends_at' => ['nullable', 'date'],
            'employee_limit' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'admin_first_name' => ['nullable', 'string', 'max:255'],
            'admin_last_name' => ['nullable', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255'],
            'send_owner_invitation' => ['nullable', 'boolean'],
            'admin_password' => ['required_without:send_owner_invitation', 'nullable', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
        ];
    }
}
