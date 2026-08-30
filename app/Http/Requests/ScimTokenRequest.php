<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScimTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:80'], 'expires_in_days' => ['nullable', 'integer', 'min:1', 'max:365']];
    }
}
