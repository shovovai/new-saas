<?php

namespace App\Jobs;

use App\Models\Website;
use App\Services\Monitoring\TlsCertificateInspector;
use App\Services\Monitoring\WhoisClient;
use App\Services\Notifications\AlertDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

/**
 * Continuous checks (Functional Spec §6): online/offline, response time,
 * SSL expiry, domain expiry, DNS changes, redirect changes, homepage
 * availability. Re-checks verification at execution time, same as every
 * other scan job (Architecture §7) — a paused/un-verified site gets no
 * checks even if it was verified when this was dispatched.
 */
class RunMonitoringCheckJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $websiteId) {}

    public function handle(TlsCertificateInspector $tls, WhoisClient $whois, AlertDispatcher $alerts): void
    {
        $website = Website::find($this->websiteId);

        if (! $website || ! $website->isVerified() || $website->status === 'paused') {
            return;
        }

        $this->checkUptime($website, $alerts);
        $this->checkDnsChanges($website);

        if (str_starts_with(strtolower($website->url), 'https://')) {
            $this->checkSslExpiry($website, $tls, $alerts);
        }

        $this->checkDomainExpiry($website, $whois, $alerts);
    }

    private function checkUptime(Website $website, AlertDispatcher $alerts): void
    {
        $start = microtime(true);

        try {
            $response = Http::timeout(15)->get($website->url);
            $ms = (int) round((microtime(true) - $start) * 1000);
            $online = $response->successful();

            $website->monitoringLogs()->create([
                'check_type' => 'uptime',
                'status' => $online ? 'ok' : 'critical',
                'metric_value' => $ms,
                'details' => ['status_code' => $response->status()],
            ]);

            if (! $online) {
                $alerts->notify($website, 'site_down', 'Site is down', "{$website->domain} returned HTTP {$response->status()}.");
            }
        } catch (\Throwable $e) {
            $website->monitoringLogs()->create([
                'check_type' => 'uptime',
                'status' => 'critical',
                'details' => ['error' => $e->getMessage()],
            ]);

            $alerts->notify($website, 'site_down', 'Site is down', "{$website->domain} is unreachable: {$e->getMessage()}");
        }
    }

    private function checkSslExpiry(Website $website, TlsCertificateInspector $tls, AlertDispatcher $alerts): void
    {
        $cert = $tls->inspect($website->domain);

        if (! $cert || ! $cert['expires_at']) {
            return;
        }

        $daysLeft = (int) now()->diffInDays($cert['expires_at'], false);
        $status = $daysLeft < 0 ? 'critical' : ($daysLeft < 14 ? 'warning' : 'ok');

        $website->monitoringLogs()->create([
            'check_type' => 'ssl_expiry',
            'status' => $status,
            'metric_value' => $daysLeft,
            'details' => ['expires_at' => $cert['expires_at']->toIso8601String()],
        ]);

        if ($status !== 'ok') {
            $alerts->notify($website, 'ssl_expiring', 'SSL certificate expiring', "{$website->domain}'s certificate ".($daysLeft < 0 ? 'has expired' : "expires in {$daysLeft} day(s)").'.');
        }
    }

    private function checkDomainExpiry(Website $website, WhoisClient $whois, AlertDispatcher $alerts): void
    {
        $expiresAt = $whois->expiresAt($website->domain);

        if (! $expiresAt) {
            return;
        }

        $daysLeft = (int) now()->diffInDays($expiresAt, false);
        $status = $daysLeft < 0 ? 'critical' : ($daysLeft < 30 ? 'warning' : 'ok');

        $website->monitoringLogs()->create([
            'check_type' => 'domain_expiry',
            'status' => $status,
            'metric_value' => $daysLeft,
            'details' => ['expires_at' => $expiresAt->toIso8601String()],
        ]);

        if ($status !== 'ok') {
            $alerts->notify($website, 'domain_expiring', 'Domain expiring', "{$website->domain} ".($daysLeft < 0 ? 'has expired' : "expires in {$daysLeft} day(s)").'.');
        }
    }

    private function checkDnsChanges(Website $website): void
    {
        try {
            $records = collect(dns_get_record($website->domain, DNS_A) ?: [])->pluck('ip')->sort()->values()->all();
        } catch (\Throwable) {
            return;
        }

        $previous = $website->monitoringLogs()->where('check_type', 'dns_snapshot')->latest()->first();
        $previousIps = $previous?->details['ips'] ?? null;

        if ($previousIps !== null && $previousIps !== $records) {
            $website->monitoringLogs()->create([
                'check_type' => 'dns_change',
                'status' => 'warning',
                'details' => ['previous' => $previousIps, 'current' => $records],
            ]);
        }

        $website->monitoringLogs()->create([
            'check_type' => 'dns_snapshot',
            'status' => 'ok',
            'details' => ['ips' => $records],
        ]);
    }
}
