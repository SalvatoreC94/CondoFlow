<?php

namespace App\Notifications;

use App\Models\Condominium;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserInvited extends Notification
{
    public function __construct(
        public string $token,
        public ?Condominium $condominium = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = rtrim(config('app.frontend_url'), '/')."/accetta-invito/{$this->token}";

        return (new MailMessage)
            ->subject('Sei stato invitato su CondoFlow')
            ->greeting("Ciao {$notifiable->name},")
            ->line($this->condominium
                ? "Sei stato invitato a far parte di \"{$this->condominium->name}\" su CondoFlow."
                : 'Sei stato invitato su CondoFlow.')
            ->action('Imposta la tua password', $url)
            ->line('Il link è valido per 7 giorni.');
    }
}
