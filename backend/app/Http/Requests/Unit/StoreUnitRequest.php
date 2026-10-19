<?php

namespace App\Http\Requests\Unit;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('condominium'));
    }

    public function rules(): array
    {
        return [
            'building_id' => ['nullable', 'integer', Rule::exists('buildings', 'id')->where('condominium_id', $this->route('condominium')->id)],
            'code' => ['required', 'string', 'max:50'],
            'floor' => ['nullable', 'string', 'max:20'],
            'type' => ['required', Rule::in(['apartment', 'garage', 'cellar', 'shop', 'other'])],
            'surface_sqm' => ['nullable', 'numeric', 'min:0'],
            'millesimi' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
