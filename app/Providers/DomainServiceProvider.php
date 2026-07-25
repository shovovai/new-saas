<?php

namespace App\Providers;

use App\Services\AI\AiProviderInterface;
use App\Services\AI\AnthropicProvider;
use App\Services\Scanning\AccessibilityScanService;
use App\Services\Scanning\AccessibilityScanServiceInterface;
use App\Services\Scanning\PerformanceScanService;
use App\Services\Scanning\PerformanceScanServiceInterface;
use App\Services\Scanning\SecurityScanService;
use App\Services\Scanning\SecurityScanServiceInterface;
use App\Services\Scanning\SeoScanService;
use App\Services\Scanning\SeoScanServiceInterface;
use Illuminate\Support\ServiceProvider;

/**
 * Binds each pluggable engine to its interface (Architecture §3) so the
 * concrete implementation — e.g. swapping this seed PerformanceScanService
 * for a real Lighthouse/Playwright runner, or Claude for OpenAI/Gemini —
 * can change without touching controllers or jobs.
 */
class DomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PerformanceScanServiceInterface::class, PerformanceScanService::class);
        $this->app->bind(SeoScanServiceInterface::class, SeoScanService::class);
        $this->app->bind(SecurityScanServiceInterface::class, SecurityScanService::class);
        $this->app->bind(AccessibilityScanServiceInterface::class, AccessibilityScanService::class);

        $this->app->bind(AiProviderInterface::class, AnthropicProvider::class);
    }
}
