<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class BrandedPasswordResetNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $recipientName = $notifiable->name ?: 'there';
        $url = $this->resetUrl($notifiable);
        $viewData = [
            'appName' => config('app.name', 'Gosika Office Management System'),
            'recipientName' => $recipientName,
            'resetUrl' => $url,
            'expiresIn' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60),
        ];

        return (new MailMessage)
            ->subject('Reset your password')
            ->view('emails.auth.password-reset', $viewData)
            ->text('emails.auth.password-reset-text', $viewData);
    }
}
