<?php

namespace App\Http\Requests\Assembly;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssemblyMinutesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('uploadMinutes', $this->route('assembly'));
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:15360', 'mimes:pdf,doc,docx'],
        ];
    }
}
