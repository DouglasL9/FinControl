<?php

namespace App\Notifications;

use App\Models\Company;
use App\Notifications\Concerns\BuildsBrandedMail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CompanyInvitationNotification extends Notification
{
    use BuildsBrandedMail;

    public function __construct(
        private readonly string $companyName,
        private readonly string $inviterName,
        private readonly string $acceptUrl,
        private readonly ?Company $company = null,
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
        $brand = $this->brandViewData($this->company);

        return (new MailMessage)
            ->subject("Convite para {$this->companyName}")
            ->markdown('mail.company-invitation', [...$brand, 'companyName' => $this->companyName, 'inviterName' => $this->inviterName, 'acceptUrl' => $this->acceptUrl]);
    }
}
