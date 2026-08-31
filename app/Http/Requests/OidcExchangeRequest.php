<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OidcExchangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'exchange_code' => ['required', 'string', 'size:64'],
        ];
    }
}
