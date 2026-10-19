<?php

namespace App\Notifications;

use App\Models\Announcement;
use Illuminate\Notifications\Notification;

class AnnouncementPublished extends Notification
{
    public function __construct(public Announcement $announcement) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'announcement.published',
            'announcement_id' => $this->announcement->id,
            'title' => 'Nuova comunicazione',
            'message' => $this->announcement->title,
        ];
    }
}
