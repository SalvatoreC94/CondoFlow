<?php

namespace App\Http\Requests\Installment;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInstallmentChargeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('markPaid', $this->route('installmentCharge')->installment);
    }

    public function rules(): array
    {
        return [
            'paid' => ['required', 'boolean'],
        ];
    }
}
