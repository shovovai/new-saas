<?php

namespace App\Services\Monitoring;

use Illuminate\Support\Carbon;

/**
 * Real WHOIS client: queries IANA's root WHOIS server to find the
 * authoritative registry for the domain's TLD, then queries that server
 * directly (the standard two-step WHOIS resolution — no third-party API,
 * no API key). Parses whichever expiry field format that registry uses.
 */
class WhoisClient
{
    private const EXPIRY_PATTERNS = [
        '/Registry Expiry Date:\s*(.+)/i',
        '/Registrar Registration Expiration Date:\s*(.+)/i',
        '/Expiration Date:\s*(.+)/i',
        '/Expiry Date:\s*(.+)/i',
        '/paid-till:\s*(.+)/i',
        '/expire:\s*(.+)/i',
    ];

    public function expiresAt(string $domain): ?Carbon
    {
        $tld = strtolower(substr(strrchr($domain, '.'), 1));

        if (! $tld) {
            return null;
        }

        $referral = $this->query('whois.iana.org', $tld);
        $registryServer = $this->extractReferralServer($referral);

        if (! $registryServer) {
            return null;
        }

        $record = $this->query($registryServer, $domain);

        return $this->extractExpiry($record);
    }

    private function query(string $server, string $query): ?string
    {
        $socket = @fsockopen($server, 43, $errno, $errstr, 8);

        if (! $socket) {
            return null;
        }

        fwrite($socket, "{$query}\r\n");
        stream_set_timeout($socket, 8);

        $response = '';
        while (! feof($socket)) {
            $response .= fgets($socket, 1024);
        }

        fclose($socket);

        return $response !== '' ? $response : null;
    }

    private function extractReferralServer(?string $response): ?string
    {
        if (! $response) {
            return null;
        }

        if (preg_match('/whois:\s*(\S+)/i', $response, $m)) {
            return trim($m[1]);
        }

        return null;
    }

    private function extractExpiry(?string $response): ?Carbon
    {
        if (! $response) {
            return null;
        }

        foreach (self::EXPIRY_PATTERNS as $pattern) {
            if (preg_match($pattern, $response, $m)) {
                try {
                    return Carbon::parse(trim($m[1]));
                } catch (\Throwable) {
                    continue;
                }
            }
        }

        return null;
    }
}
