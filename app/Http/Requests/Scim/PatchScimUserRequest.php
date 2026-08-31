<?php

namespace App\Http\Requests\Scim;

use Illuminate\Foundation\Http\FormRequest;

class PatchScimUserRequest extends FormRequest
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
            'Operations' => ['required', 'array', 'min:1'],
            'Operations.*.op' => ['required', 'string', 'in:replace,Replace,REPLACE'],
            'Operations.*.path' => ['required', 'string', 'in:active,userName,name.givenName,name.familyName'],
            'Operations.*.value' => ['required'],
        ];
    }
}
