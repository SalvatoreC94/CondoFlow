<?php

namespace App\Http\Requests\Installment;

use App\Enums\SplitMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInstallmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageFinances', $this->route('condominium'));
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'total_amount' => ['required', 'numeric', 'min:0.01', 'max:9999999.99'],
            'split_method' => ['required', Rule::in(array_map(fn ($m) => $m->value, SplitMethod::cases()))],
            'due_date' => ['required', 'date'],
        ];
    }
}
