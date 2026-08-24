<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Condominium;
use App\Models\User;
use App\Policies\Concerns\ChecksCondominiumAccess;

class CondominiumPolicy
{
    use ChecksCondominiumAccess;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Condominium $condominium): bool
    {
        return $this->hasAccessTo($user, $condominium->id);
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Administrator;
    }

    public function update(User $user, Condominium $condominium): bool
    {
        return $this->administers($user, $condominium->id);
    }

    public function delete(User $user, Condominium $condominium): bool
    {
        return $this->administers($user, $condominium->id);
    }

    public function manageStaff(User $user, Condominium $condominium): bool
    {
        return $this->administers($user, $condominium->id);
    }
}
