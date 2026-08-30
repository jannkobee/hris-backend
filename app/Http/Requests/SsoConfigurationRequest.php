<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SsoConfigurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'issuer_url' => ['required', 'url', 'regex:/^https:\/\//i'],
            'client_id' => ['required', 'string', 'max:255'],
            'client_secret' => ['required', 'string', 'max:2000'],
            'scopes' => ['nullable', 'array'],
            'scopes.*' => ['string', 'max:80'],
            'is_active' => ['boolean'],
        ];
    }
}
