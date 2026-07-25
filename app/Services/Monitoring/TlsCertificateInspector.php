<?php

namespace App\Services\Monitoring;

use Illuminate\Support\Carbon;

/**
 * Real TLS certificate inspection via a raw socket connection — shared by
 * SecurityScanService (one-off scan) and the monitoring uptime check
 * (continuous expiry tracking), so there is exactly one implementation of
 * "read the live certificate" in the app.
 */
class TlsCertificateInspector
{
    /**
     * @return array{expires_at: ?Carbon}|null
     */
    public function inspect(string $domain): ?array
    {
        $context = stream_context_create(['ssl' => [
            'capture_peer_cert' => true,
            'verify_peer' => false,
            'verify_peer_name' => false,
        ]]);

        $client = @stream_socket_client(
            "ssl://{$domain}:443",
            $errno,
            $errstr,
            8,
            STREAM_CLIENT_CONNECT,
            $context,
        );

        if (! $client) {
            return null;
        }

        $params = stream_context_get_params($client);
        fclose($client);

        $cert = $params['options']['ssl']['peer_certificate'] ?? null;

        if (! $cert) {
            return null;
        }

        $parsed = openssl_x509_parse($cert);
        $validTo = $parsed['validTo_time_t'] ?? null;

        return ['expires_at' => $validTo ? Carbon::createFromTimestamp($validTo) : null];
    }
}
