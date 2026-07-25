<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Http;

/**
 * Posts a plain JSON payload to a user-supplied webhook URL — for
 * integrating with tools that don't have a bespoke channel here (Zapier,
 * n8n, a custom internal endpoint, etc).
 */
class GenericWebhookSender
{
    public function send(string $webhookUrl, string $alertType, string $title, string $message, array $context = []): void
    {
        $response = Http::post($webhookUrl, [
            'alert_type' => $alertType,
            'title' => $title,
            'message' => $message,
            'context' => $context,
        ]);

        if (! $response->successful()) {
            throw new NotificationChannelException('Generic webhook send failed: '.$response->body());
        }
    }
}
