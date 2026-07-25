<?php

use App\Http\Middleware\AuthenticateApiKey;
use App\Http\Middleware\CheckMaintenanceMode;
use App\Http\Middleware\EnsurePlatformAdmin;
use App\Http\Middleware\EnsureTeamMembership;
use App\Http\Middleware\EnsureWebsiteVerified;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            CheckMaintenanceMode::class,
        ]);

        $middleware->alias([
            'website.verified' => EnsureWebsiteVerified::class,
            'team.member' => EnsureTeamMembership::class,
            'platform.admin' => EnsurePlatformAdmin::class,
            'api.key' => AuthenticateApiKey::class,
        ]);

        // Payment provider webhooks are unauthenticated third-party POSTs
        // with no CSRF token — each gateway verifies its own signature
        // instead (see app/Services/Billing/Gateways).
        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
