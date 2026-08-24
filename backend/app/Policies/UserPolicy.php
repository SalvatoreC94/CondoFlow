<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ChecksCondominiumAccess;

class UserPolicy
{
    use ChecksCondominiumAccess;

    /**
     * Whether $user may manage (invite/edit/remove) $target within condominium $condominiumId.
     */
    public function manage(User $user, User $target, int $condominiumId): bool
    {
        return $this->administers($user, $condominiumId);
    }

    public function view(User $user, User $target): bool
    {
        if ($user->id === $target->id) {
            return true;
        }

        // Any condominium where both users have overlapping access is enough
        // for staff to view a resident's basic profile.
        $userCondos = $user->visibleCondominiumIds();
        $targetCondos = $target->visibleCondominiumIds();

        return $user->role->value !== 'condomino' && $userCondos->intersect($targetCondos)->isNotEmpty();
    }

    public function updateProfile(User $user, User $target): bool
    {
        return $user->id === $target->id;
    }
}
