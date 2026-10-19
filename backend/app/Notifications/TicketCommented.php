<?php

namespace App\Notifications;

use App\Models\TicketComment;
use Illuminate\Notifications\Notification;

class TicketCommented extends Notification
{
    public function __construct(public TicketComment $comment) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $ticket = $this->comment->ticket;

        return [
            'type' => 'ticket.commented',
            'ticket_id' => $ticket->id,
            'title' => 'Nuovo commento',
            'message' => "Nuovo commento su \"{$ticket->title}\".",
        ];
    }
}
