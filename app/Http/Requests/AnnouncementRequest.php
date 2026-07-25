<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:5000000'],
            'published_at' => ['nullable', 'date_format:Y-m-d'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
