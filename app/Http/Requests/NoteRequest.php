<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string', 'max:1000000'],
            'category' => ['nullable', 'string', 'max:80'],
            'color' => ['required', Rule::in(['primary', 'secondary', 'info', 'success', 'warning', 'error'])],
            'is_pinned' => ['required', 'boolean'],
            'is_archived' => ['required', 'boolean'],
        ];
    }
}
