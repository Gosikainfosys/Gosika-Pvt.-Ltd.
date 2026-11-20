<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountEmailChangedNotification extends Notification
{
    public function __construct(
        private readonly string $name,
        private readonly string $newEmail,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Gosika account email was changed')
            ->greeting('Hello '.$this->name.',')
            ->line('The email address for your Gosika administrator account was changed to '.$this->newEmail.'.')
            ->line('If you did not make this change, contact your system administrator immediately.');
    }
}
