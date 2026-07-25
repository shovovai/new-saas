<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $alertType,
        private readonly string $title,
        private readonly string $message,
        private readonly array $context = [],
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("SiteGuardian AI: {$this->title}")
            ->line($this->message)
            ->when(! empty($this->context), fn ($mail) => $mail->line('Details: '.json_encode($this->context)));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'alert_type' => $this->alertType,
            'title' => $this->title,
            'message' => $this->message,
            'context' => $this->context,
        ];
    }
}
