<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InviteUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageStaff', $this->route('condominium'));
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'required_without:phone', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'required_without:email', 'string', 'max:30', 'unique:users,phone'],
            'role' => ['required', Rule::in(['caretaker', 'condomino'])],
            'unit_id' => ['required_if:role,condomino', 'nullable', 'integer', 'exists:units,id'],
            'relationship' => ['required_if:role,condomino', 'nullable', Rule::in(['owner', 'tenant'])],
        ];
    }
}
