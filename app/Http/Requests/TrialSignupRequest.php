<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class TrialSignupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'organization_name' => ['required', 'string', 'max:255'],
            // The public trial flow chooses the workspace address from the
            // organization name. Ignore a legacy client-provided slug.
            'slug' => ['nullable', 'string'],
            'country_code' => ['required', 'string', 'size:2', 'alpha'],
            'timezone' => ['required', 'timezone'],
            'plan_code' => ['sometimes', 'in:basic_free,starter,growth,business'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
            'terms_accepted' => ['accepted'],
        ];
    }
}
