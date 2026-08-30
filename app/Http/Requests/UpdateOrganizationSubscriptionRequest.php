<?php

namespace App\Http\Requests;

use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrganizationSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_code' => ['required', 'string', Rule::in(array_keys(config('plans.plans')))],
            'subscription_status' => ['required', Rule::in(Organization::SUBSCRIPTION_STATUSES)],
            'trial_ends_at' => ['nullable', 'date'],
            'current_period_ends_at' => ['nullable', 'date'],
            'employee_limit' => ['nullable', 'integer', 'min:1', 'max:100000'],
        ];
    }
}
