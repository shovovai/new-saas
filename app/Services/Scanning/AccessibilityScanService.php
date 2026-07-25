<?php

namespace App\Services\Scanning;

use App\Models\AccessibilityReport;
use App\Models\Website;
use Illuminate\Support\Facades\Http;

class AccessibilityScanService implements AccessibilityScanServiceInterface
{
    public function run(Website $website): AccessibilityReport
    {
        $findings = [];
        $violations = 0;
        $score = 100;

        try {
            $body = Http::timeout(15)->get($website->url)->body();

            $missingAlt = preg_match_all('/<img(?![^>]*\balt=)[^>]*>/i', $body);
            if ($missingAlt > 0) {
                $violations += $missingAlt;
                $findings[] = $this->finding('images', 'warn', "{$missingAlt} image(s) missing alt text", 'Screen readers cannot describe images without alt text.');
                $score -= min(20, $missingAlt * 2);
            }

            if (! preg_match('/<html[^>]+lang=/i', $body)) {
                $violations++;
                $findings[] = $this->finding('lang', 'warn', 'Missing lang attribute on <html>', 'Declaring a page language helps assistive technology pronounce content correctly.');
                $score -= 10;
            }

            $findings[] = $this->finding(
                'engine',
                'info',
                'Full WCAG audit pending',
                'A complete axe-core style audit (contrast, ARIA, focus order) is not wired up in this environment yet.',
            );
        } catch (\Throwable $e) {
            $findings[] = $this->finding('availability', 'critical', 'Site unreachable', $e->getMessage());
            $score = null;
        }

        return $website->accessibilityReports()->create([
            'score' => $score !== null ? max(0, $score) : null,
            'violations_count' => $violations,
            'findings' => $findings,
        ]);
    }

    private function finding(string $category, string $severity, string $title, string $explanation): array
    {
        return compact('category', 'severity', 'title', 'explanation');
    }
}
