<?php

namespace App\Notifications;

use App\Models\Intervention;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class InterventionCompleted extends Notification
{
    public function __construct(public Intervention $intervention) {}

    public function via(object $notifiable): array
    {
        return ['database', WebPushChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'intervention.completed',
            'ticket_id' => $this->intervention->ticket_id,
            'title' => 'Intervento completato',
            'message' => "L'intervento per \"{$this->intervention->ticket->title}\" è stato completato.",
        ];
    }

    public function toWebPush(object $notifiable): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Intervento completato')
            ->body("L'intervento per \"{$this->intervention->ticket->title}\" è stato completato.")
            ->data(['url' => "/app/segnalazioni/{$this->intervention->ticket_id}"]);
    }
}
