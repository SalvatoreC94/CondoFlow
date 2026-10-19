<?php

namespace App\Notifications;

use App\Models\Intervention;
use Illuminate\Notifications\Notification;

class InterventionCompleted extends Notification
{
    public function __construct(public Intervention $intervention) {}

    public function via(object $notifiable): array
    {
        return ['database'];
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
}
