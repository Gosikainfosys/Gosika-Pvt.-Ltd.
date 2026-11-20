<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordChangedNotification extends Notification
{
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Gosika account password was changed')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('The password for your Gosika administrator account was changed.')
            ->line('If you did not make this change, reset your password immediately and contact your system administrator.');
    }
}
