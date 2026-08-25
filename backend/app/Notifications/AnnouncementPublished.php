<?php

namespace App\Notifications;

use App\Models\Announcement;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class AnnouncementPublished extends Notification
{
    public function __construct(public Announcement $announcement) {}

    public function via(object $notifiable): array
    {
        return ['database', WebPushChannel::class];
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

    public function toWebPush(object $notifiable): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Nuova comunicazione')
            ->body($this->announcement->title)
            ->data(['url' => "/app/comunicazioni/{$this->announcement->id}"]);
    }
}
