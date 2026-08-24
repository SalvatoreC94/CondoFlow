<?php

namespace App\Http\Requests\Ticket;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('uploadAttachment', $this->route('ticket'));
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required', 'file', 'max:8192',
                'mimes:jpg,jpeg,png,webp,heic,pdf',
            ],
        ];
    }
}
