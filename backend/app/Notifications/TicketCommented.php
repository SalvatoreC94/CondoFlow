<?php

namespace App\Notifications;

use App\Enums\UserRole;
use App\Models\TicketComment;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class TicketCommented extends Notification
{
    public function __construct(public TicketComment $comment) {}

    public function via(object $notifiable): array
    {
        return ['database', WebPushChannel::class];
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

    public function toWebPush(object $notifiable): WebPushMessage
    {
        $ticket = $this->comment->ticket;

        $url = match ($notifiable->role ?? null) {
            UserRole::Administrator => "/admin/segnalazioni/{$ticket->id}",
            UserRole::Caretaker => "/custode/segnalazioni/{$ticket->id}",
            default => "/app/segnalazioni/{$ticket->id}",
        };

        return (new WebPushMessage)
            ->title('Nuovo commento')
            ->body("Nuovo commento su \"{$ticket->title}\".")
            ->data(['url' => $url]);
    }
}
