<?php

namespace App\Http\Requests\Scim;

use Illuminate\Foundation\Http\FormRequest;

class StoreScimUserRequest extends FormRequest
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
        return $this->userRules();
    }

    protected function userRules(): array
    {
        return [
            'externalId' => ['required', 'string', 'max:255'],
            'userName' => ['required', 'email', 'max:255'],
            'name' => ['required', 'array'],
            'name.givenName' => ['required', 'string', 'max:255'],
            'name.familyName' => ['required', 'string', 'max:255'],
            'active' => ['nullable', 'boolean'],
        ];
    }
}
