<?php

namespace App\Policies;

use App\Models\Assembly;
use App\Models\User;
use App\Policies\Concerns\ChecksCondominiumAccess;

class AssemblyPolicy
{
    use ChecksCondominiumAccess;

    public function view(User $user, Assembly $assembly): bool
    {
        return $this->hasAccessTo($user, $assembly->condominium_id);
    }

    public function create(User $user): bool
    {
        return $user->role->value === 'administrator';
    }

    public function update(User $user, Assembly $assembly): bool
    {
        return $this->administers($user, $assembly->condominium_id);
    }

    public function delete(User $user, Assembly $assembly): bool
    {
        return $this->administers($user, $assembly->condominium_id);
    }

    public function manageResolutions(User $user, Assembly $assembly): bool
    {
        return $this->administers($user, $assembly->condominium_id);
    }

    public function uploadMinutes(User $user, Assembly $assembly): bool
    {
        return $this->administers($user, $assembly->condominium_id);
    }
}
