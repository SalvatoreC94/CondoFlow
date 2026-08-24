<?php

namespace App\Http\Requests\Announcement;

use App\Enums\AnnouncementPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('announcement'));
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'content' => ['sometimes', 'required', 'string', 'max:10000'],
            'priority' => ['sometimes', 'required', Rule::in(array_map(fn ($p) => $p->value, AnnouncementPriority::cases()))],
            'expires_at' => ['nullable', 'date'],
        ];
    }
}
