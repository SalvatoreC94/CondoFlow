<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Notifications\Notification;

class DocumentPublished extends Notification
{
    public function __construct(public Document $document) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'document.published',
            'document_id' => $this->document->id,
            'title' => 'Nuovo documento',
            'message' => $this->document->title,
        ];
    }
}
