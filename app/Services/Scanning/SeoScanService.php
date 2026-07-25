<?php

namespace App\Services\Scanning;

use App\Models\SeoReport;
use App\Models\Website;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SeoScanService implements SeoScanServiceInterface
{
    private const MAX_LINKS_CHECKED = 10;

    public function run(Website $website): SeoReport
    {
        $findings = [];
        $missingAlt = 0;
        $brokenLinks = 0;
        $hasSitemap = null;
        $hasRobots = null;
        $score = 100;

        try {
            $response = Http::timeout(15)->get($website->url);
            $body = $response->body();

            if (! preg_match('/<title[^>]*>(.*?)<\/title>/is', $body)) {
                $findings[] = $this->finding('title', 'critical', 'Missing <title> tag', 'Every indexable page needs a unique, descriptive title tag.');
                $score -= 20;
            }

            if (! preg_match('/<meta[^>]+name=["\']description["\']/i', $body)) {
                $findings[] = $this->finding('meta', 'warn', 'Missing meta description', 'Add a meta description to improve click-through from search results.');
                $score -= 10;
            }

            if (! preg_match('/<link[^>]+rel=["\']canonical["\']/i', $body)) {
                $findings[] = $this->finding('canonical', 'warn', 'Missing canonical tag', 'A canonical tag helps avoid duplicate content issues.');
                $score -= 5;
            }

            $h1Count = preg_match_all('/<h1[\s>]/i', $body);
            if ($h1Count === 0) {
                $findings[] = $this->finding('headings', 'warn', 'No <h1> heading found', 'Every page should have exactly one top-level heading describing its content.');
                $score -= 10;
            } elseif ($h1Count > 1) {
                $findings[] = $this->finding('headings', 'info', "Multiple <h1> tags found ({$h1Count})", 'Search engines generally expect a single H1 per page for the clearest content hierarchy.');
                $score -= 5;
            }

            if (! preg_match('/<script[^>]+type=["\']application\/ld\+json["\']/i', $body)) {
                $findings[] = $this->finding('schema', 'info', 'No structured data (schema.org) found', 'Adding JSON-LD structured data can unlock rich results in search listings.');
                $score -= 5;
            }

            $ogTags = ['og:title', 'og:description', 'og:image'];
            $missingOg = array_values(array_filter($ogTags, fn (string $tag) => ! preg_match('/<meta[^>]+property=["\']'.preg_quote($tag, '/').'["\']/i', $body)));
            if (! empty($missingOg)) {
                $findings[] = $this->finding('social', 'info', 'Missing Open Graph tags: '.implode(', ', $missingOg), 'Open Graph tags control how the page looks when shared on social media and in chat previews.');
                $score -= 5;
            }

            if (! preg_match('/<meta[^>]+name=["\']twitter:card["\']/i', $body)) {
                $findings[] = $this->finding('social', 'info', 'Missing Twitter Card tag', 'A twitter:card meta tag improves how links look when shared on X/Twitter.');
            }

            if (preg_match('/<meta[^>]+name=["\']robots["\'][^>]+content=["\'][^"\']*noindex/i', $body)) {
                $findings[] = $this->finding('indexability', 'critical', 'Page is marked noindex', 'This page tells search engines not to index it — remove the noindex directive if that was unintentional.');
                $score -= 25;
            }

            if (($response->header('X-Robots-Tag') ?? '') && str_contains(strtolower($response->header('X-Robots-Tag')), 'noindex')) {
                $findings[] = $this->finding('indexability', 'critical', 'X-Robots-Tag header blocks indexing', 'The response header sends a noindex directive — remove it if unintentional.');
                $score -= 25;
            }

            $missingAlt = preg_match_all('/<img(?![^>]*\balt=)[^>]*>/i', $body);

            if ($missingAlt > 0) {
                $findings[] = $this->finding('images', 'warn', "{$missingAlt} image(s) missing alt text", 'Alt text improves accessibility and image SEO.');
                $score -= min(15, $missingAlt * 2);
            }

            [$internalCount, $externalCount, $brokenLinks] = $this->analyzeLinks($body, $website->url);

            if ($brokenLinks > 0) {
                $findings[] = $this->finding('links', 'warn', "{$brokenLinks} broken link(s) found on the homepage", 'Broken links hurt user experience and crawl efficiency — fix or remove them.');
                $score -= min(20, $brokenLinks * 5);
            }
        } catch (\Throwable $e) {
            $findings[] = $this->finding('availability', 'critical', 'Site unreachable', $e->getMessage());
            $score = null;
        }

        $hasRobots = $this->urlExists($website->url, '/robots.txt');
        $hasSitemap = $this->urlExists($website->url, '/sitemap.xml');

        if ($hasRobots === false) {
            $findings[] = $this->finding('robots', 'info', 'No robots.txt found', 'A robots.txt helps guide search engine crawlers.');
        }

        if ($hasSitemap === false) {
            $findings[] = $this->finding('sitemap', 'info', 'No sitemap.xml found', 'A sitemap helps search engines discover all your pages.');
        }

        $findings[] = $this->finding(
            'engine',
            'info',
            'Single-page scan only',
            'This scan checks the homepage only — full-site duplicate title/description detection requires crawling every page and is not wired up in this environment yet.',
        );

        return $website->seoReports()->create([
            'score' => $score !== null ? max(0, $score) : null,
            'broken_links_count' => $brokenLinks,
            'missing_alt_count' => $missingAlt,
            'has_sitemap' => $hasSitemap,
            'has_robots_txt' => $hasRobots,
            'findings' => $findings,
        ]);
    }

    /**
     * @return array{0: int, 1: int, 2: int} [internalCount, externalCount, brokenCount]
     */
    private function analyzeLinks(string $body, string $baseUrl): array
    {
        preg_match_all('/<a[^>]+href=["\']([^"\']+)["\']/i', $body, $matches);
        $hrefs = array_unique($matches[1] ?? []);
        $host = parse_url($baseUrl, PHP_URL_HOST);

        $internal = 0;
        $external = 0;
        $checked = 0;
        $broken = 0;

        foreach ($hrefs as $href) {
            if (Str::startsWith($href, ['#', 'mailto:', 'tel:', 'javascript:'])) {
                continue;
            }

            $linkHost = parse_url($href, PHP_URL_HOST);
            $isInternal = $linkHost === null || $linkHost === $host;
            $isInternal ? $internal++ : $external++;

            if ($checked >= self::MAX_LINKS_CHECKED) {
                continue;
            }

            $absolute = $linkHost === null ? rtrim($baseUrl, '/').'/'.ltrim($href, '/') : $href;

            try {
                $checked++;
                if (! Http::timeout(6)->head($absolute)->successful()) {
                    $broken++;
                }
            } catch (\Throwable) {
                $broken++;
            }
        }

        return [$internal, $external, $broken];
    }

    private function urlExists(string $base, string $path): ?bool
    {
        try {
            return Http::timeout(8)->get(rtrim($base, '/').$path)->successful();
        } catch (\Throwable) {
            return null;
        }
    }

    private function finding(string $category, string $severity, string $title, string $explanation): array
    {
        return compact('category', 'severity', 'title', 'explanation');
    }
}
