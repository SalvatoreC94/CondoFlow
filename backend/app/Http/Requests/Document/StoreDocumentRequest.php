<?php

namespace App\Http\Requests\Document;

use App\Enums\DocumentVisibility;
use App\Models\Document;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Document::class);
    }

    public function rules(): array
    {
        return [
            'condominium_id' => ['required', 'integer', 'exists:condominiums,id'],
            'document_category_id' => ['required', 'integer', 'exists:document_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'visibility' => ['required', Rule::in(array_map(fn ($v) => $v->value, DocumentVisibility::cases()))],
            'file' => [
                'required', 'file', 'max:15360',
                'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png',
            ],
        ];
    }
}
