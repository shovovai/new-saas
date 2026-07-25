<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Http;

/**
 * Real Telegram Bot API sendMessage call
 * (https://core.telegram.org/bots/api#sendmessage). Requires a bot token
 * (config('services.telegram.bot_token')) and the target chat_id (each
 * team/user provides their own chat_id after starting a chat with the bot).
 */
class TelegramSender
{
    public function isConfigured(): bool
    {
        return (bool) config('services.telegram.bot_token');
    }

    public function send(string $chatId, string $message): void
    {
        if (! $this->isConfigured()) {
            throw new NotificationChannelException('Telegram bot is not configured (set TELEGRAM_BOT_TOKEN).');
        }

        $token = config('services.telegram.bot_token');

        $response = Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $message,
        ]);

        if (! $response->successful()) {
            throw new NotificationChannelException('Telegram send failed: '.$response->body());
        }
    }
}
