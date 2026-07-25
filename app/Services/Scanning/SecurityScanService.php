<?php

namespace App\Services\Scanning;

use App\Models\SecurityReport;
use App\Models\Website;
use App\Services\Monitoring\TlsCertificateInspector;
use Illuminate\Support\Facades\Http;

class SecurityScanService implements SecurityScanServiceInterface
{
    private const REQUIRED_HEADERS = [
        'strict-transport-security',
        'x-content-type-options',
        'x-frame-options',
        'content-security-policy',
    ];

    private const SENSITIVE_PATHS = ['/.env', '/.git/HEAD', '/wp-config.php.bak', '/config.php.bak', '/.aws/credentials'];

    private const DIRECTORY_LISTING_PATHS = ['/assets/', '/uploads/', '/backup/', '/files/'];

    public function __construct(private readonly TlsCertificateInspector $tlsInspector) {}

    public function run(Website $website): SecurityReport
    {
        $findings = [];
        $score = 100;
        $sslValid = str_starts_with(strtolower($website->url), 'https://');
        $missingHeaders = [];
        $sslExpiresAt = null;

        if (! $sslValid) {
            $findings[] = $this->finding('ssl', 'critical', 'Site is not served over HTTPS', 'All traffic should be encrypted with a valid TLS certificate.');
            $score -= 30;
        } else {
            $cert = $this->tlsInspector->inspect($website->domain);

            if ($cert === null) {
                $findings[] = $this->finding('ssl', 'warn', 'Could not read TLS certificate', 'The certificate could not be retrieved for inspection — verify the site is reachable on port 443.');
            } else {
                $sslExpiresAt = $cert['expires_at'];

                if ($sslExpiresAt) {
                    $daysLeft = now()->diffInDays($sslExpiresAt, false);

                    if ($daysLeft < 0) {
                        $findings[] = $this->finding('ssl', 'critical', 'TLS certificate has expired', "The certificate expired on {$sslExpiresAt->toDateString()}.");
                        $sslValid = false;
                        $score -= 40;
                    } elseif ($daysLeft < 14) {
                        $findings[] = $this->finding('ssl', 'warn', 'TLS certificate expiring soon', "The certificate expires in {$daysLeft} day(s), on {$sslExpiresAt->toDateString()}.");
                        $score -= 10;
                    }
                }
            }
        }

        try {
            $response = Http::timeout(15)->get($website->url);
            $headers = array_change_key_case($response->headers(), CASE_LOWER);

            foreach (self::REQUIRED_HEADERS as $header) {
                if (! isset($headers[$header])) {
                    $missingHeaders[] = $header;
                    $findings[] = $this->finding('headers', 'warn', "Missing {$header} header", 'This security header helps mitigate common web attacks.');
                    $score -= 10;
                }
            }

            $this->analyzeCookies($headers, $findings, $score);
            $this->detectMixedContent($response->body(), $sslValid, $findings, $score);
            $this->fingerprintTech($headers, $response->body(), $findings);
        } catch (\Throwable $e) {
            $findings[] = $this->finding('availability', 'critical', 'Site unreachable', $e->getMessage());
            $score = null;
        }

        $exposedFile = false;

        foreach (self::SENSITIVE_PATHS as $path) {
            if ($this->isExposed($website->url, $path)) {
                $exposedFile = true;
                $findings[] = $this->finding('exposure', 'critical', "Sensitive file exposed at {$path}", 'This file should never be publicly reachable — it may leak credentials or source history.');
                $score = $score !== null ? $score - 30 : null;
            }
        }

        foreach (self::DIRECTORY_LISTING_PATHS as $path) {
            if ($this->hasDirectoryListing($website->url, $path)) {
                $findings[] = $this->finding('exposure', 'warn', "Directory listing enabled at {$path}", 'Disable directory indexing on your web server to avoid exposing file structure.');
                $score = $score !== null ? $score - 10 : null;
            }
        }

        $this->checkEmailAuthentication($website->domain, $findings);

        return $website->securityReports()->create([
            'score' => $score !== null ? max(0, $score) : null,
            'ssl_valid' => $sslValid,
            'ssl_expires_at' => $sslExpiresAt,
            'has_sensitive_file_exposure' => $exposedFile,
            'missing_headers' => $missingHeaders,
            'findings' => $findings,
        ]);
    }

    private function analyzeCookies(array $headers, array &$findings, int &$score): void
    {
        $setCookie = $headers['set-cookie'] ?? null;

        if (! $setCookie) {
            return;
        }

        $cookies = is_array($setCookie) ? $setCookie : [$setCookie];

        foreach ($cookies as $cookie) {
            $lower = strtolower($cookie);
            $issues = [];

            if (! str_contains($lower, 'secure')) {
                $issues[] = 'missing Secure flag';
            }
            if (! str_contains($lower, 'httponly')) {
                $issues[] = 'missing HttpOnly flag';
            }
            if (! str_contains($lower, 'samesite')) {
                $issues[] = 'missing SameSite attribute';
            }

            if ($issues) {
                $name = explode('=', $cookie)[0];
                $findings[] = $this->finding('cookies', 'warn', "Cookie \"{$name}\" is missing security flags", implode(', ', $issues).' — set these attributes to reduce session hijacking and CSRF risk.');
                $score -= 5;
            }
        }
    }

    private function detectMixedContent(string $body, bool $isHttps, array &$findings, int &$score): void
    {
        if (! $isHttps) {
            return;
        }

        $count = preg_match_all('/(?:src|href)=["\']http:\/\/[^"\']+["\']/i', $body);

        if ($count > 0) {
            $findings[] = $this->finding('mixed_content', 'warn', "{$count} insecure (http://) resource reference(s) found", 'Loading resources over plain HTTP on an HTTPS page triggers mixed-content warnings and can be blocked by browsers.');
            $score -= min(15, $count * 3);
        }
    }

    private function fingerprintTech(array $headers, string $body, array &$findings): void
    {
        $detected = [];

        if (isset($headers['server'])) {
            $detected[] = 'Server: '.(is_array($headers['server']) ? implode(', ', $headers['server']) : $headers['server']);
        }

        if (isset($headers['x-powered-by'])) {
            $detected[] = 'X-Powered-By: '.(is_array($headers['x-powered-by']) ? implode(', ', $headers['x-powered-by']) : $headers['x-powered-by']);
        }

        if (preg_match('/<meta[^>]+name=["\']generator["\'][^>]+content=["\']([^"\']+)["\']/i', $body, $m)) {
            $detected[] = 'Generator: '.$m[1];
        }

        if ($detected) {
            $findings[] = $this->finding('fingerprint', 'info', 'Technology fingerprint detected', 'Publicly visible: '.implode('; ', $detected).'. Consider suppressing version banners to reduce reconnaissance value for attackers.');
        }
    }

    private function checkEmailAuthentication(string $domain, array &$findings): void
    {
        try {
            $txtRecords = dns_get_record($domain, DNS_TXT) ?: [];
        } catch (\Throwable) {
            return;
        }

        $hasSpf = collect($txtRecords)->contains(fn ($r) => str_starts_with($r['txt'] ?? '', 'v=spf1'));

        if (! $hasSpf) {
            $findings[] = $this->finding('email_auth', 'info', 'No SPF record found', 'An SPF TXT record helps prevent email spoofing of your domain.');
        }

        try {
            $dmarcRecords = dns_get_record("_dmarc.{$domain}", DNS_TXT) ?: [];
        } catch (\Throwable) {
            $dmarcRecords = [];
        }

        $hasDmarc = collect($dmarcRecords)->contains(fn ($r) => str_starts_with($r['txt'] ?? '', 'v=DMARC1'));

        if (! $hasDmarc) {
            $findings[] = $this->finding('email_auth', 'info', 'No DMARC record found', 'A DMARC record at _dmarc.'.$domain.' tells receiving mail servers how to handle unauthenticated mail claiming to be from your domain.');
        }
    }

    private function isExposed(string $base, string $path): bool
    {
        try {
            return Http::timeout(8)->get(rtrim($base, '/').$path)->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    private function hasDirectoryListing(string $base, string $path): bool
    {
        try {
            $response = Http::timeout(8)->get(rtrim($base, '/').$path);

            return $response->successful() && str_contains(strtolower($response->body()), 'index of /');
        } catch (\Throwable) {
            return false;
        }
    }

    private function finding(string $category, string $severity, string $title, string $explanation): array
    {
        return compact('category', 'severity', 'title', 'explanation');
    }
}
