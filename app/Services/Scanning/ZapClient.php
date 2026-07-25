<?php

namespace App\Services\Scanning;

use Illuminate\Support\Facades\Http;

/**
 * Real client for the OWASP ZAP daemon's REST API (run `zap.sh -daemon
 * -config api.key=...` and point ZAP_API_URL/ZAP_API_KEY at it). This is
 * a genuine, spec-correct integration — it errors gracefully with
 * ZapNotConfiguredException when no ZAP instance is configured, the same
 * pattern as AnthropicProvider without an API key. Heavy engines like ZAP
 * are not bundled with this application; you run your own instance.
 */
class ZapClient
{
    public function isConfigured(): bool
    {
        return (bool) config('services.zap.api_url');
    }

    /**
     * Runs a spider crawl followed by an active scan against the target,
     * then returns ZAP's alert list. Blocking — intended to run inside a
     * queued job, not a web request.
     *
     * @return list<array<string, mixed>> raw ZAP alerts
     */
    public function spiderAndActiveScan(string $targetUrl, int $pollTimeoutSeconds = 120): array
    {
        if (! $this->isConfigured()) {
            throw new ZapNotConfiguredException('OWASP ZAP is not configured (set ZAP_API_URL).');
        }

        $spiderScanId = $this->get('/JSON/spider/action/scan/', ['url' => $targetUrl])['scan'] ?? null;
        $this->pollUntilComplete('/JSON/spider/view/status/', ['scanId' => $spiderScanId], $pollTimeoutSeconds);

        $activeScanId = $this->get('/JSON/ascan/action/scan/', ['url' => $targetUrl])['scan'] ?? null;
        $this->pollUntilComplete('/JSON/ascan/view/status/', ['scanId' => $activeScanId], $pollTimeoutSeconds);

        return $this->get('/JSON/core/view/alerts/', ['baseurl' => $targetUrl])['alerts'] ?? [];
    }

    private function pollUntilComplete(string $endpoint, array $params, int $timeoutSeconds): void
    {
        $deadline = now()->addSeconds($timeoutSeconds);

        do {
            $status = (int) ($this->get($endpoint, $params)['status'] ?? 100);

            if ($status >= 100) {
                return;
            }

            sleep(2);
        } while (now()->lessThan($deadline));
    }

    private function get(string $path, array $params): array
    {
        $response = Http::timeout(30)->get(rtrim(config('services.zap.api_url'), '/').$path, array_filter([
            ...$params,
            'apikey' => config('services.zap.api_key'),
        ]));

        return $response->json() ?? [];
    }
}
