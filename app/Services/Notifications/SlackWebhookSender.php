<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Http;

/**
 * Real Slack Incoming Webhook POST (https://api.slack.com/messaging/webhooks).
 */
class SlackWebhookSender
{
    public function send(string $webhookUrl, string $title, string $message): void
    {
        $response = Http::post($webhookUrl, [
            'text' => "*{$title}*\n{$message}",
        ]);

        if (! $response->successful()) {
            throw new NotificationChannelException('Slack webhook send failed: '.$response->body());
        }
    }
}
