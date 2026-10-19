<?php

namespace App\Notifications;

use App\Models\Assembly;
use Illuminate\Notifications\Notification;

class AssemblyScheduled extends Notification
{
    public function __construct(public Assembly $assembly) {}

    public function via(object $notifiable): array
    {
        return ['database'];
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
}
