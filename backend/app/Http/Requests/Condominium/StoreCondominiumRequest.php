<?php

namespace App\Http\Requests\Condominium;

use App\Models\Condominium;
use Illuminate\Foundation\Http\FormRequest;

class StoreCondominiumRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Condominium::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:4'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'country' => ['nullable', 'string', 'size:2'],
            'total_units' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
        ];
    }
}
