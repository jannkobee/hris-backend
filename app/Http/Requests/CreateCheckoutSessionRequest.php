<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateCheckoutSessionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'plan_code' => ['required', 'in:starter,growth,business'],
            'billing_interval' => ['required', 'in:month,year'],
            'billing_email' => ['nullable', 'email'],
            'success_url' => ['required', 'url', 'regex:/^https?:\/\//i'],
            'cancel_url' => ['required', 'url', 'regex:/^https?:\/\//i'],
        ];
    }
}
