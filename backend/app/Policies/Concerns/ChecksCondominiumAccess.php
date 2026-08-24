<?php

namespace App\Policies\Concerns;

use App\Enums\UserRole;
use App\Models\User;

/**
 * Core multi-tenancy boundary used by every policy: a user may only touch
 * a condominium's data if they administer it, are assigned to it as a
 * caretaker, or live in it (through a unit). Never trust an ID from the
 * request alone — always resolve access through this trait.
 */
trait ChecksCondominiumAccess
{
    protected function administers(User $user, int $condominiumId): bool
    {
        return $user->role === UserRole::Administrator
            && $user->administeredCondominiums()->whereKey($condominiumId)->exists();
    }

    protected function caretakes(User $user, int $condominiumId): bool
    {
        return $user->role === UserRole::Caretaker
            && $user->assignedCondominiums()->where('condominiums.id', $condominiumId)->exists();
    }

    protected function residesIn(User $user, int $condominiumId): bool
    {
        return $user->role === UserRole::Condomino
            && $user->units()->where('units.condominium_id', $condominiumId)->exists();
    }

    protected function hasAccessTo(User $user, int $condominiumId): bool
    {
        return $this->administers($user, $condominiumId)
            || $this->caretakes($user, $condominiumId)
            || $this->residesIn($user, $condominiumId);
    }

    /**
     * Staff (administrator or caretaker) have management capabilities over
     * a condominium's data; residents only have read/self-service access.
     */
    protected function isStaffFor(User $user, int $condominiumId): bool
    {
        return $this->administers($user, $condominiumId) || $this->caretakes($user, $condominiumId);
    }
}
