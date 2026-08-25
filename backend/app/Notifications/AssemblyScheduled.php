<?php

namespace App\Notifications;

use App\Models\Assembly;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class AssemblyScheduled extends Notification
{
    public function __construct(public Assembly $assembly) {}

    public function via(object $notifiable): array
    {
        return ['database', WebPushChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'assembly.scheduled',
            'assembly_id' => $this->assembly->id,
            'title' => 'Nuova assemblea convocata',
            'message' => $this->assembly->title,
        ];
    }

    public function toWebPush(object $notifiable): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Nuova assemblea convocata')
            ->body($this->assembly->title)
            ->data(['url' => "/app/assemblee/{$this->assembly->id}"]);
    }
}
