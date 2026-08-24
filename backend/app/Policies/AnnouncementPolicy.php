<?php

namespace App\Policies;

use App\Models\Announcement;
use App\Models\User;
use App\Policies\Concerns\ChecksCondominiumAccess;

class AnnouncementPolicy
{
    use ChecksCondominiumAccess;

    public function view(User $user, Announcement $announcement): bool
    {
        return $this->hasAccessTo($user, $announcement->condominium_id)
            && ($this->isStaffFor($user, $announcement->condominium_id) || $announcement->isVisibleTo($user));
    }

    public function create(User $user): bool
    {
        return $user->role->value === 'administrator';
    }

    public function update(User $user, Announcement $announcement): bool
    {
        return $this->administers($user, $announcement->condominium_id);
    }

    public function delete(User $user, Announcement $announcement): bool
    {
        return $this->administers($user, $announcement->condominium_id);
    }
}
