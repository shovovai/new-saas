<?php

namespace App\Services\Verification;

use App\Models\Website;
use App\Models\WebsiteVerification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MetaTagVerificationService implements VerificationServiceInterface
{
    public function method(): string
    {
        return 'meta_tag';
    }

    public function provision(Website $website): WebsiteVerification
    {
        return WebsiteVerification::firstOrCreate(
            ['website_id' => $website->id, 'method' => $this->method()],
            ['token' => Str::random(32), 'status' => 'pending'],
        );
    }

    public function metaTag(WebsiteVerification $verification): string
    {
        return sprintf('<meta name="siteguardian-site-verification" content="%s" />', $verification->token);
    }

    public function verify(WebsiteVerification $verification): bool
    {
        $website = $verification->website;

        try {
            $response = Http::timeout(10)->get($website->url);
        } catch (\Throwable $e) {
            $verification->increment('attempts');
            $verification->update(['last_error' => 'Could not reach '.$website->url]);

            return false;
        }

        if (! $response->successful()) {
            $verification->increment('attempts');
            $verification->update(['last_error' => "Homepage returned HTTP {$response->status()}."]);

            return false;
        }

        $found = (bool) preg_match(
            '/<meta[^>]+name=["\']siteguardian-site-verification["\'][^>]+content=["\']'.preg_quote($verification->token, '/').'["\'][^>]*>/i',
            $response->body(),
        );

        if ($found) {
            return true;
        }

        $verification->increment('attempts');
        $verification->update([
            'last_error' => 'Meta tag not found on the homepage — make sure it is in the <head> and the page is publicly reachable.',
        ]);

        return false;
    }

    public function instructions(WebsiteVerification $verification): array
    {
        return [
            'summary' => 'Add an HTML meta tag',
            'instructions' => sprintf(
                "Add this tag inside the <head> of your homepage:\n%s",
                $this->metaTag($verification),
            ),
            'estimated_minutes' => 5,
        ];
    }
}
