<?php

namespace App\Notifications;

use App\Models\User;
use App\Notifications\Concerns\BuildsBrandedMail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoginVerificationCode extends Notification
{
    use BuildsBrandedMail;

    public function __construct(
        public readonly string $code,
        private readonly ?User $user = null,
    ) {}

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
        $brand = $this->brandViewData($this->singleActiveCompanyOf($this->user));
        $firstName = $this->firstNameOf($this->user?->name);

        return (new MailMessage)
            ->subject(__('Your sign-in verification code'))
            ->markdown('mail.login-code', [
                ...$brand,
                'code' => $this->code,
                'greeting' => $firstName === null ? 'Olá!' : "Olá, {$firstName}!",
            ]);
    }
}
