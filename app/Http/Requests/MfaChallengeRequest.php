<?php

namespace App\Http\Requests;

class MfaChallengeRequest extends MfaCodeRequest
{
    public function rules(): array
    {
        return [
            'challenge' => ['required', 'string', 'size:64'],
            ...parent::rules(),
        ];
    }
}
