<?php

namespace App\Http\Requests;

class DisableMfaRequest extends MfaCodeRequest
{
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password'],
            ...parent::rules(),
        ];
    }
}
