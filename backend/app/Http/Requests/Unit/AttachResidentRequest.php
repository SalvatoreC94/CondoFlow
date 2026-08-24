<?php

namespace App\Http\Requests\Unit;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttachResidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageResidents', $this->route('unit'));
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'relationship' => ['required', Rule::in(['owner', 'tenant'])],
            'is_primary' => ['boolean'],
        ];
    }
}
