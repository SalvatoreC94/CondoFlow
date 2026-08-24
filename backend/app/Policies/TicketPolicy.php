<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;
use App\Policies\Concerns\ChecksCondominiumAccess;

class TicketPolicy
{
    use ChecksCondominiumAccess;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Ticket $ticket): bool
    {
        if ($this->isStaffFor($user, $ticket->condominium_id)) {
            return true;
        }

        return $this->residesIn($user, $ticket->condominium_id) && (
            $ticket->created_by === $user->id
            || ($ticket->unit_id && $user->units()->whereKey($ticket->unit_id)->exists())
        );
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Ticket $ticket): bool
    {
        return $this->isStaffFor($user, $ticket->condominium_id);
    }

    public function updateStatus(User $user, Ticket $ticket): bool
    {
        return $this->isStaffFor($user, $ticket->condominium_id);
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $this->administers($user, $ticket->condominium_id);
    }

    public function comment(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket);
    }

    public function viewInternalComments(User $user, Ticket $ticket): bool
    {
        return $this->isStaffFor($user, $ticket->condominium_id);
    }

    public function uploadAttachment(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket);
    }

    public function deleteAttachment(User $user, Ticket $ticket): bool
    {
        return $this->isStaffFor($user, $ticket->condominium_id);
    }

    public function manageInterventions(User $user, Ticket $ticket): bool
    {
        return $this->isStaffFor($user, $ticket->condominium_id);
    }
}
