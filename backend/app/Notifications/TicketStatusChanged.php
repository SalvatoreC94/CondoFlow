<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class TicketStatusChanged extends Notification
{
    public function __construct(public Ticket $ticket) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail', WebPushChannel::class];
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

    public function toWebPush(object $notifiable): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Aggiornamento segnalazione')
            ->body("\"{$this->ticket->title}\": {$this->ticket->status->label()}.")
            ->data(['url' => "/app/segnalazioni/{$this->ticket->id}"]);
    }
}
