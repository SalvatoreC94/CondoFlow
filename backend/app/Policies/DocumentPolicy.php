<?php

namespace App\Policies;

use App\Enums\DocumentVisibility;
use App\Models\Document;
use App\Models\User;
use App\Policies\Concerns\ChecksCondominiumAccess;

class DocumentPolicy
{
    use ChecksCondominiumAccess;

    public function view(User $user, Document $document): bool
    {
        if (! $this->hasAccessTo($user, $document->condominium_id)) {
            return false;
        }

        if ($this->isStaffFor($user, $document->condominium_id)) {
            return true;
        }

        return match ($document->visibility) {
            DocumentVisibility::All, DocumentVisibility::Condomini => true,
            default => false,
        };
    }

    public function create(User $user): bool
    {
        return $user->role->value === 'administrator';
    }

    public function delete(User $user, Document $document): bool
    {
        return $this->administers($user, $document->condominium_id);
    }
}
