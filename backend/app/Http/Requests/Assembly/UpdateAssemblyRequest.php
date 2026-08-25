<?php

namespace App\Http\Requests\Assembly;

use App\Enums\AssemblyStatus;
use App\Enums\AssemblyType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssemblyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('assembly'));
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'type' => ['sometimes', 'required', Rule::in(array_map(fn ($t) => $t->value, AssemblyType::cases()))],
            'status' => ['sometimes', 'required', Rule::in(array_map(fn ($s) => $s->value, AssemblyStatus::cases()))],
            'agenda' => ['sometimes', 'required', 'string', 'max:5000'],
            'location' => ['nullable', 'string', 'max:255'],
            'scheduled_at' => ['sometimes', 'required', 'date'],
            'held_at' => ['nullable', 'date'],
        ];
    }
}
