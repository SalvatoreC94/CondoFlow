<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;
use App\Policies\Concerns\ChecksCondominiumAccess;

class ExpensePolicy
{
    use ChecksCondominiumAccess;

    public function view(User $user, Expense $expense): bool
    {
        return $this->administers($user, $expense->condominium_id);
    }

    public function update(User $user, Expense $expense): bool
    {
        return $this->administers($user, $expense->condominium_id);
    }

    public function delete(User $user, Expense $expense): bool
    {
        return $this->administers($user, $expense->condominium_id);
    }
}
