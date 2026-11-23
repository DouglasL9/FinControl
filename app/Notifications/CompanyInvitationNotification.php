<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CompanyInvitationNotification extends Notification
{
    public function __construct(
        private readonly string $companyName,
        private readonly string $inviterName,
        private readonly string $acceptUrl,
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
        return (new MailMessage)
            ->subject("Convite para {$this->companyName}")
            ->greeting('Você foi convidado!')
            ->line("{$this->inviterName} convidou você para acompanhar as contas a pagar de {$this->companyName}.")
            ->action('Aceitar convite', $this->acceptUrl)
            ->line('O convite expira em 7 dias e só pode ser aceito pelo endereço convidado.');
    }
}
