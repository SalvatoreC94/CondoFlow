<?php

namespace App\Policies;

use App\Models\Building;
use App\Models\User;
use App\Policies\Concerns\ChecksCondominiumAccess;

class BuildingPolicy
{
    use ChecksCondominiumAccess;

    public function view(User $user, Building $building): bool
    {
        return $this->hasAccessTo($user, $building->condominium_id);
    }

    public function update(User $user, Building $building): bool
    {
        return $this->administers($user, $building->condominium_id);
    }

    public function delete(User $user, Building $building): bool
    {
        return $this->administers($user, $building->condominium_id);
    }
}
