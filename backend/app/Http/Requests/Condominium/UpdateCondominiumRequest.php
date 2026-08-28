<?php

namespace App\Http\Requests\Condominium;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCondominiumRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('condominium'));
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'address' => ['sometimes', 'required', 'string', 'max:255'],
            'city' => ['sometimes', 'required', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:4'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'country' => ['nullable', 'string', 'size:2'],
            'total_units' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'brand_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }
}
