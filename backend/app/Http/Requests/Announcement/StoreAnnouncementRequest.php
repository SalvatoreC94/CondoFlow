<?php

namespace App\Http\Requests\Announcement;

use App\Enums\AnnouncementAudience;
use App\Enums\AnnouncementPriority;
use App\Models\Announcement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Announcement::class);
    }

    public function rules(): array
    {
        return [
            'condominium_id' => ['required', 'integer', 'exists:condominiums,id'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:10000'],
            'priority' => ['required', Rule::in(array_map(fn ($p) => $p->value, AnnouncementPriority::cases()))],
            'audience' => ['required', Rule::in(array_map(fn ($a) => $a->value, AnnouncementAudience::cases()))],
            'building_ids' => ['required_if:audience,buildings', 'array'],
            'building_ids.*' => ['integer', 'exists:buildings,id'],
            'user_ids' => ['required_if:audience,users', 'array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'published_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:published_at'],
        ];
    }
}
