<?php

namespace App\Notifications;

use App\Enums\UserRole;
use App\Models\Ticket;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class TicketCreated extends Notification
{
    public function __construct(public Ticket $ticket) {}

    public function via(object $notifiable): array
    {
        return ['database', WebPushChannel::class];
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

    public function toWebPush(object $notifiable): WebPushMessage
    {
        $url = $notifiable->role === UserRole::Caretaker
            ? "/custode/segnalazioni/{$this->ticket->id}"
            : "/admin/segnalazioni/{$this->ticket->id}";

        return (new WebPushMessage)
            ->title('Nuova segnalazione')
            ->body("\"{$this->ticket->title}\" è stata segnalata.")
            ->data(['url' => $url]);
    }
}
