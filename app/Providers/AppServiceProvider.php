<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Public REST API (Functional Spec §14): keyed by the API key
        // itself so one team's usage never throttles another's, falling
        // back to IP for the unauthenticated/malformed-token case.
        RateLimiter::for('api', function (Request $request) {
            $apiKey = $request->attributes->get('api_key');

            return Limit::perMinute(60)->by($apiKey?->id ?? $request->ip());
        });
    }
}
