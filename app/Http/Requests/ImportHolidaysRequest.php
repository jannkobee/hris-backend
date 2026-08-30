<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportHolidaysRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['year' => ['required', 'integer', 'min:1900', 'max:2200'], 'country_code' => ['nullable', 'string', 'size:2', 'alpha']];
    }
}
