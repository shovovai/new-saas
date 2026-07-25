<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Http;

/**
 * Real Discord Webhook POST (https://discord.com/developers/docs/resources/webhook).
 */
class DiscordWebhookSender
{
    public function send(string $webhookUrl, string $title, string $message): void
    {
        $response = Http::post($webhookUrl, [
            'content' => "**{$title}**\n{$message}",
        ]);

        if (! $response->successful()) {
            throw new NotificationChannelException('Discord webhook send failed: '.$response->body());
        }
    }
}
