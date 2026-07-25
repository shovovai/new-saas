<?php

namespace App\Services\Verification;

use App\Models\Website;
use App\Models\WebsiteVerification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class HtmlFileVerificationService implements VerificationServiceInterface
{
    public function method(): string
    {
        return 'html_file';
    }

    public function provision(Website $website): WebsiteVerification
    {
        return WebsiteVerification::firstOrCreate(
            ['website_id' => $website->id, 'method' => $this->method()],
            ['token' => 'siteguardian-'.Str::random(24), 'status' => 'pending'],
        );
    }

    public function fileName(WebsiteVerification $verification): string
    {
        return $verification->token.'.txt';
    }

    public function fileUrl(Website $website, WebsiteVerification $verification): string
    {
        return rtrim($website->url, '/').'/'.$this->fileName($verification);
    }

    public function verify(WebsiteVerification $verification): bool
    {
        $website = $verification->website;
        $url = $this->fileUrl($website, $verification);

        try {
            $response = Http::timeout(10)->get($url);
        } catch (\Throwable $e) {
            $verification->increment('attempts');
            $verification->update(['last_error' => 'Could not reach '.$url]);

            return false;
        }

        if ($response->successful() && trim($response->body()) === $verification->token) {
            return true;
        }

        $verification->increment('attempts');
        $verification->update([
            'last_error' => $response->successful()
                ? 'File found but contents did not match the expected token.'
                : "Verification file not found (HTTP {$response->status()}) at {$url}.",
        ]);

        return false;
    }

    public function instructions(WebsiteVerification $verification): array
    {
        $website = $verification->website;

        return [
            'summary' => 'Upload an HTML verification file',
            'instructions' => sprintf(
                "Create a file named \"%s\" containing exactly:\n%s\n\nUpload it to your site root so it is reachable at:\n%s",
                $this->fileName($verification),
                $verification->token,
                $this->fileUrl($website, $verification),
            ),
            'estimated_minutes' => 5,
        ];
    }
}
