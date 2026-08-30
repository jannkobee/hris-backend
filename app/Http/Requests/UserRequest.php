<?php

namespace App\Http\Requests;

use App\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
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
        $organizationId = app(TenantContext::class)->organization()->getKey();
        $userId = $this->route('user');

        return [
            'role_id' => [
                'required',
                Rule::exists('roles', 'id')
                    ->where(fn ($query) => $query->where('organization_id', $organizationId)),
            ],
            'first_name' => 'required|string',
            'middle_name' => 'nullable|string',
            'last_name' => 'required|string',
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')
                    ->where(fn ($query) => $query->where('organization_id', $organizationId))
                    ->ignore($userId, 'id'),
            ],
            'gender' => 'required|in:Male,Female',
            'birthday' => 'required|date|date_format:Y-m-d',
        ];
    }
}
