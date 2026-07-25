<?php

namespace App\Services\Verification;

use App\Models\Website;
use App\Models\WebsiteVerification;
use Illuminate\Support\Str;

class DnsTxtVerificationService implements VerificationServiceInterface
{
    public function method(): string
    {
        return 'dns_txt';
    }

    public function provision(Website $website): WebsiteVerification
    {
        return WebsiteVerification::firstOrCreate(
            ['website_id' => $website->id, 'method' => $this->method()],
            ['token' => 'siteguardian-verify='.Str::random(32), 'status' => 'pending'],
        );
    }

    public function recordName(Website $website): string
    {
        return '_siteguardian.'.$website->domain;
    }

    public function verify(WebsiteVerification $verification): bool
    {
        $website = $verification->website;
        $host = $this->recordName($website);

        try {
            $records = dns_get_record($host, DNS_TXT) ?: [];
        } catch (\Throwable $e) {
            $verification->increment('attempts');
            $verification->update(['last_error' => 'DNS lookup failed: '.$e->getMessage()]);

            return false;
        }

        foreach ($records as $record) {
            if (($record['txt'] ?? null) === $verification->token) {
                return true;
            }
        }

        $verification->increment('attempts');
        $verification->update([
            'last_error' => 'TXT record not found — DNS changes can take up to 24 hours to propagate.',
        ]);

        return false;
    }

    public function instructions(WebsiteVerification $verification): array
    {
        return [
            'summary' => 'Add a DNS TXT record',
            'instructions' => sprintf(
                "Add a TXT record for host \"%s\" with the value:\n%s",
                $this->recordName($verification->website),
                $verification->token,
            ),
            'estimated_minutes' => 15,
        ];
    }
}
