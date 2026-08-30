<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MfaCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'min:6', 'max:32'],
        ];
    }
}
