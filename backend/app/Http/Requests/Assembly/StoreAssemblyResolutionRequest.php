<?php

namespace App\Http\Requests\Assembly;

use App\Enums\ResolutionOutcome;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssemblyResolutionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageResolutions', $this->route('assembly'));
    }

    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:2000'],
            'outcome' => ['required', Rule::in(array_map(fn ($o) => $o->value, ResolutionOutcome::cases()))],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
