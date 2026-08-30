<?php

namespace App\Http\Requests;

use App\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest
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
        $roleId = $this->route('role');
        $organizationId = app(TenantContext::class)->organization()->getKey();

        return [
            'name' => [
                'required',
                'string',
                Rule::unique('roles', 'name')
                    ->where(fn ($query) => $query->where('organization_id', $organizationId))
                    ->ignore($roleId, 'id'),
            ],
            'description' => 'nullable|string',
        ];
    }
}
