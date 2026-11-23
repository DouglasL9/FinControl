<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoginVerificationCode extends Notification
{
    public function __construct(public readonly string $code) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Your sign-in verification code'))
            ->line(__('Use this code to finish signing in:'))
            ->line($this->code)
            ->line(__('This code expires in 10 minutes. If you did not try to sign in, you can ignore this email.'));
    }
}
