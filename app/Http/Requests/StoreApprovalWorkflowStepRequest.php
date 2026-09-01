<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreApprovalWorkflowStepRequest extends FormRequest
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
        return ['sequence' => ['required', 'integer', 'min:1'], 'approver_type' => ['required', 'in:manager,user,role'], 'approver_id' => ['nullable', 'uuid'], 'conditions' => ['nullable', 'array'], 'sla_hours' => ['nullable', 'integer', 'min:1', 'max:8760']];
    }
}
