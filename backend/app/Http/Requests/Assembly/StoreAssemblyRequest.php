<?php

namespace App\Http\Requests\Assembly;

use App\Enums\AssemblyType;
use App\Models\Assembly;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssemblyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Assembly::class);
    }

    public function rules(): array
    {
        return [
            'condominium_id' => ['required', 'integer', 'exists:condominiums,id'],
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(array_map(fn ($t) => $t->value, AssemblyType::cases()))],
            'agenda' => ['required', 'string', 'max:5000'],
            'location' => ['nullable', 'string', 'max:255'],
            'scheduled_at' => ['required', 'date'],
        ];
    }
}
