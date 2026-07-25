<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Http;

/**
 * Real integration against Twilio's Messages REST API
 * (https://www.twilio.com/docs/sms/api/message-resource) — no SDK
 * dependency, just Basic-Auth'd form POST.
 */
class TwilioClient
{
    public function isConfigured(): bool
    {
        return (bool) (config('services.twilio.sid') && config('services.twilio.token') && config('services.twilio.from'));
    }

    public function sendSms(string $to, string $body): void
    {
        if (! $this->isConfigured()) {
            throw new NotificationChannelException('Twilio is not configured (set TWILIO_SID/TWILIO_TOKEN/TWILIO_FROM).');
        }

        $sid = config('services.twilio.sid');

        $response = Http::asForm()
            ->withBasicAuth($sid, config('services.twilio.token'))
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'From' => config('services.twilio.from'),
                'To' => $to,
                'Body' => $body,
            ]);

        if (! $response->successful()) {
            throw new NotificationChannelException('Twilio SMS send failed: '.$response->body());
        }
    }
}
