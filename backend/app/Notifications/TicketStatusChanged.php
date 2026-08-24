<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketStatusChanged extends Notification
{
    public function __construct(public Ticket $ticket) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'ticket.status_changed',
            'ticket_id' => $this->ticket->id,
            'title' => 'Aggiornamento segnalazione',
            'message' => "\"{$this->ticket->title}\": {$this->ticket->status->label()}.",
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Aggiornamento sulla tua segnalazione: {$this->ticket->title}")
            ->greeting("Ciao {$notifiable->name},")
            ->line("Lo stato della tua segnalazione \"{$this->ticket->title}\" è cambiato in: {$this->ticket->status->label()}.")
            ->line($this->ticket->status->value === 'resolved' ? 'Grazie per la segnalazione!' : '');
    }
}
