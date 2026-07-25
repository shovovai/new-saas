<?php

namespace App\Services\Scanning;

use App\Models\PerformanceReport;
use App\Models\Website;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

/**
 * Real browser-based performance check: shells out to
 * scanning-scripts/performance-scan.mjs, which drives a real headless
 * Chromium (via Playwright) and reports actual Core Web Vitals — not a
 * simulated or hardcoded number. See that script for what each metric
 * means and INP's fundamental limitation in automated scans.
 */
class PerformanceScanService implements PerformanceScanServiceInterface
{
    public function run(Website $website): PerformanceReport
    {
        $findings = [];
        $ttfbMs = null;
        $lcpMs = null;
        $cls = null;
        $tbtMs = null;
        $score = null;

        $result = $this->runBrowserScan($website->url);

        if (isset($result['error'])) {
            $findings[] = $this->finding('availability', 'critical', 'Site unreachable', $result['error']);
        } else {
            $ttfbMs = $result['ttfb_ms'] ?? null;
            $lcpMs = $result['lcp_ms'] ?? null;
            $cls = $result['cls'] ?? null;
            $tbtMs = $result['tbt_ms'] ?? null;

            if (($result['status'] ?? null) !== null && $result['status'] >= 400) {
                $findings[] = $this->finding('availability', 'critical', 'Homepage did not return a successful response', "Received HTTP {$result['status']}.");
            }

            $score = $this->scoreFromVitals($lcpMs, $cls, $tbtMs);

            if ($ttfbMs !== null && $ttfbMs > 800) {
                $findings[] = $this->finding('ttfb', 'warn', 'Slow time to first byte', "TTFB was {$ttfbMs}ms — consider server-side caching or a faster origin.");
            }

            if ($lcpMs !== null && $lcpMs > 2500) {
                $findings[] = $this->finding('lcp', $lcpMs > 4000 ? 'critical' : 'warn', 'Slow Largest Contentful Paint', "LCP was {$lcpMs}ms (good is ≤ 2500ms) — optimize the largest above-the-fold image/text block, preload hero assets, and reduce render-blocking resources.");
            }

            if ($cls !== null && $cls > 0.1) {
                $findings[] = $this->finding('cls', $cls > 0.25 ? 'critical' : 'warn', 'Layout shifting during load', 'CLS was '.$cls.' (good is ≤ 0.1) — reserve space for images/embeds and avoid injecting content above existing content.');
            }

            if ($tbtMs !== null && $tbtMs > 200) {
                $findings[] = $this->finding('tbt', $tbtMs > 600 ? 'critical' : 'warn', 'Main thread blocked by long tasks', "Total Blocking Time was {$tbtMs}ms — split up long JavaScript tasks and defer non-critical scripts.");
            }

            $findings[] = $this->finding(
                'engine',
                'info',
                'INP is a field metric',
                'Interaction to Next Paint requires a real user interaction and is reported from real visitors, not an automated scan — Total Blocking Time above is the standard lab-metric proxy (same approach Lighthouse uses).',
            );
        }

        return $website->performanceReports()->create([
            'score' => $score,
            'ttfb_ms' => $ttfbMs,
            'lcp_ms' => $lcpMs,
            'cls' => $cls,
            'inp_ms' => $tbtMs,
            'findings' => $findings,
        ]);
    }

    /**
     * @return array{status?: ?int, ttfb_ms?: ?int, lcp_ms?: ?int, cls?: ?float, tbt_ms?: ?int, error?: string}
     */
    private function runBrowserScan(string $url): array
    {
        $script = config('scanning.performance_script');

        if (! file_exists($script)) {
            return ['error' => 'Performance scan script not found.'];
        }

        try {
            $result = Process::timeout(config('scanning.timeout_seconds', 30))
                ->env(array_filter([
                    'PLAYWRIGHT_CHROMIUM_PATH' => config('scanning.chromium_executable'),
                ]))
                ->run([config('scanning.node_binary', 'node'), $script, $url]);
        } catch (\Throwable $e) {
            return ['error' => 'Could not start the browser scan: '.$e->getMessage()];
        }

        $output = trim($result->output());

        if (! $result->successful() || $output === '') {
            Log::warning('Performance scan process failed', ['url' => $url, 'error_output' => $result->errorOutput()]);

            return ['error' => 'Browser scan process failed to complete.'];
        }

        $decoded = json_decode($output, true);

        if (! is_array($decoded)) {
            return ['error' => 'Browser scan returned unexpected output.'];
        }

        return $decoded;
    }

    private function scoreFromVitals(?int $lcpMs, ?float $cls, ?int $tbtMs): ?int
    {
        if ($lcpMs === null && $cls === null && $tbtMs === null) {
            return null;
        }

        $lcpScore = $lcpMs === null ? 100 : $this->curve($lcpMs, 2500, 4000);
        $clsScore = $cls === null ? 100 : $this->curve($cls * 1000, 100, 250);
        $tbtScore = $tbtMs === null ? 100 : $this->curve($tbtMs, 200, 600);

        return (int) round(($lcpScore * 0.4) + ($tbtScore * 0.35) + ($clsScore * 0.25));
    }

    /**
     * 100 at/below $good, 0 at/above $poor, linear in between.
     */
    private function curve(float $value, float $good, float $poor): float
    {
        if ($value <= $good) {
            return 100;
        }

        if ($value >= $poor) {
            return 0;
        }

        return 100 * (1 - (($value - $good) / ($poor - $good)));
    }

    private function finding(string $category, string $severity, string $title, string $explanation): array
    {
        return compact('category', 'severity', 'title', 'explanation');
    }
}
