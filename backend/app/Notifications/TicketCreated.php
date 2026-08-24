<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Notifications\Notification;

class TicketCreated extends Notification
{
    public function __construct(public Ticket $ticket) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'ticket.created',
            'ticket_id' => $this->ticket->id,
            'title' => 'Nuova segnalazione',
            'message' => "\"{$this->ticket->title}\" è stata segnalata.",
        ];
    }
}
