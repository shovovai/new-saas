<?php

namespace App\Services\Notifications;

use App\Models\Alert;
use App\Models\Website;
use App\Notifications\AlertNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Fans a single alert event out to every channel a team/user has enabled
 * for that alert type (Functional Spec §11) — each channel is a real
 * integration (see the individual Sender classes), and a failure on one
 * channel never blocks the others.
 */
class AlertDispatcher
{
    public function __construct(
        private readonly SlackWebhookSender $slack,
        private readonly DiscordWebhookSender $discord,
        private readonly TelegramSender $telegram,
        private readonly GenericWebhookSender $webhook,
        private readonly TwilioClient $twilio,
    ) {}

    public function notify(Website $website, string $alertType, string $title, string $message, array $context = []): void
    {
        $preferences = Alert::query()
            ->where('team_id', $website->team_id)
            ->where('alert_type', $alertType)
            ->where('enabled', true)
            ->get();

        foreach ($preferences as $preference) {
            $this->dispatchToPreference($preference, $alertType, $title, $message, $context);
        }
    }

    private function dispatchToPreference(Alert $preference, string $alertType, string $title, string $message, array $context): void
    {
        $recipients = $preference->user_id
            ? collect([$preference->user])->filter()
            : $preference->team->members;

        foreach ($preference->channels ?? [] as $channel) {
            try {
                match ($channel) {
                    'email', 'push' => Notification::send($recipients, new AlertNotification($alertType, $title, $message, $context)),
                    'slack' => $preference->slack_webhook_url && $this->slack->send($preference->slack_webhook_url, $title, $message),
                    'discord' => $preference->discord_webhook_url && $this->discord->send($preference->discord_webhook_url, $title, $message),
                    'telegram' => $preference->telegram_chat_id && $this->telegram->send($preference->telegram_chat_id, "{$title}\n{$message}"),
                    'webhook' => $preference->webhook_url && $this->webhook->send($preference->webhook_url, $alertType, $title, $message, $context),
                    'sms' => $preference->phone_number && $this->twilio->sendSms($preference->phone_number, "{$title}: {$message}"),
                    default => Log::info("Unknown alert channel \"{$channel}\""),
                };
            } catch (\Throwable $e) {
                Log::warning("Alert channel \"{$channel}\" failed for alert_type \"{$alertType}\"", ['error' => $e->getMessage()]);
            }
        }
    }
}
