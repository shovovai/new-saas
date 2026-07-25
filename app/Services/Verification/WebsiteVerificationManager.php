<?php

namespace App\Services\Verification;

use App\Models\Website;
use App\Models\WebsiteVerification;
use Illuminate\Support\Collection;

/**
 * Resolves a verification method to its VerificationServiceInterface
 * implementation, and is the ONLY code path in the application allowed to
 * write Website.status / verified_method / verified_at (Architecture §5).
 */
class WebsiteVerificationManager
{
    /**
     * @var array<string, class-string<VerificationServiceInterface>>
     */
    private array $methods = [
        'dns_txt' => DnsTxtVerificationService::class,
        'html_file' => HtmlFileVerificationService::class,
        'meta_tag' => MetaTagVerificationService::class,
    ];

    public function serviceFor(string $method): VerificationServiceInterface
    {
        if (! isset($this->methods[$method])) {
            throw new \InvalidArgumentException("Unknown verification method [{$method}].");
        }

        return app($this->methods[$method]);
    }

    /**
     * @return list<string>
     */
    public function availableMethods(): array
    {
        return array_keys($this->methods);
    }

    /**
     * Provision all verification methods for a website up front, so
     * switching methods in the UI never requires regenerating tokens
     * (UIUX §3).
     *
     * @return Collection<int, WebsiteVerification>
     */
    public function provisionAll(Website $website): Collection
    {
        return collect($this->availableMethods())
            ->map(fn (string $method) => $this->serviceFor($method)->provision($website));
    }

    /**
     * Attempt verification via one method. On success this is the single
     * place that flips Website.status to verified.
     */
    public function attempt(Website $website, string $method): WebsiteVerification
    {
        $service = $this->serviceFor($method);
        $verification = $service->provision($website);

        if ($website->isVerified()) {
            return $verification;
        }

        $verified = $service->verify($verification->fresh());

        if ($verified) {
            $verification->fresh()->update([
                'status' => 'verified',
                'verified_at' => now(),
                'last_error' => null,
            ]);

            $website->update([
                'status' => 'verified',
                'verified_method' => $method,
                'verified_at' => now(),
            ]);
        } else {
            $verification->update(['status' => 'pending']);
        }

        return $verification->fresh();
    }
}
