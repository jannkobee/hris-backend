<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListPlatformOrganizationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:120'],
            'plan_code' => ['nullable', Rule::in(array_keys(config('plans.plans')))],
            'subscription_status' => ['nullable', 'in:trialing,active,past_due,suspended,cancelled'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
