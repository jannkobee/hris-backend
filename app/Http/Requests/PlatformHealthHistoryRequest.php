<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlatformHealthHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['limit' => ['sometimes', 'integer', 'min:1', 'max:100']];
    }
}
