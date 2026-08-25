<?php

namespace App\Policies;

use App\Models\Installment;
use App\Models\User;
use App\Policies\Concerns\ChecksCondominiumAccess;

class InstallmentPolicy
{
    use ChecksCondominiumAccess;

    public function view(User $user, Installment $installment): bool
    {
        return $this->administers($user, $installment->condominium_id);
    }

    public function delete(User $user, Installment $installment): bool
    {
        return $this->administers($user, $installment->condominium_id);
    }

    public function markPaid(User $user, Installment $installment): bool
    {
        return $this->administers($user, $installment->condominium_id);
    }
}
