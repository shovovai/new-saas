<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Platform-wide maintenance mode (System Settings, Functional Spec §13) —
 * a real toggle rather than a cosmetic admin field: while enabled, every
 * request from a non-platform-admin is turned away with a 503.
 */
class CheckMaintenanceMode
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('webhooks/*')) {
            return $next($request);
        }

        if (Setting::bool('maintenance_mode') && ! $request->user()?->is_platform_admin) {
            abort(503, Setting::get('maintenance_message', 'We are performing scheduled maintenance. Please check back shortly.'));
        }

        return $next($request);
    }
}
