<?php

namespace App\Http\Requests\Ticket;

use App\Enums\TicketPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'condominium_id' => ['required', 'integer', 'exists:condominiums,id'],
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'ticket_category_id' => ['required', 'integer', 'exists:ticket_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'priority' => ['required', Rule::in(array_map(fn ($p) => $p->value, TicketPriority::cases()))],
            'location' => ['nullable', 'string', 'max:255'],
        ];
    }
}
