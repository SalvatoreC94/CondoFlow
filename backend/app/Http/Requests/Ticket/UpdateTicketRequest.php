<?php

namespace App\Http\Requests\Ticket;

use App\Enums\TicketPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('ticket'));
    }

    public function rules(): array
    {
        $ticket = $this->route('ticket');

        return [
            'ticket_category_id' => ['sometimes', 'required', 'integer', 'exists:ticket_categories,id'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string', 'max:5000'],
            'priority' => ['sometimes', 'required', Rule::in(array_map(fn ($p) => $p->value, TicketPriority::cases()))],
            'location' => ['nullable', 'string', 'max:255'],
            'assigned_caretaker_id' => [
                'nullable', 'integer',
                Rule::exists('caretaker_condominium', 'user_id')->where('condominium_id', $ticket->condominium_id),
            ],
            'supplier_id' => [
                'nullable', 'integer',
                Rule::exists('supplier_condominium', 'supplier_id')->where('condominium_id', $ticket->condominium_id),
            ],
        ];
    }
}
