<?php

namespace App\Policies;

use App\Models\Unit;
use App\Models\User;
use App\Policies\Concerns\ChecksCondominiumAccess;

class UnitPolicy
{
    use ChecksCondominiumAccess;

    public function view(User $user, Unit $unit): bool
    {
        return $this->hasAccessTo($user, $unit->condominium_id);
    }

    public function update(User $user, Unit $unit): bool
    {
        return $this->administers($user, $unit->condominium_id);
    }

    public function delete(User $user, Unit $unit): bool
    {
        return $this->administers($user, $unit->condominium_id);
    }

    public function manageResidents(User $user, Unit $unit): bool
    {
        return $this->administers($user, $unit->condominium_id);
    }
}
