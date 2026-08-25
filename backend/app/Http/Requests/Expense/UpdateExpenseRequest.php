<?php

namespace App\Http\Requests\Expense;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('expense'));
    }

    public function rules(): array
    {
        return [
            'supplier_id' => [
                'nullable', 'integer',
                Rule::exists('suppliers', 'id')->where(fn ($q) => $q->where('administrator_id', $this->user()->id)),
            ],
            'category' => ['sometimes', 'required', 'string', 'max:100'],
            'description' => ['sometimes', 'required', 'string', 'max:255'],
            'amount' => ['sometimes', 'required', 'numeric', 'min:0.01', 'max:999999.99'],
            'expense_date' => ['sometimes', 'required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
