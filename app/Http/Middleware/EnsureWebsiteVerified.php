<?php

namespace App\Http\Middleware;

use App\Models\Website;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The hard, binary security gate (Architecture §2): no scan, AI query,
 * monitoring job, or pen test may run against a website whose status is
 * not `verified` — no plan, role, or admin override bypasses this for
 * actually running new scans against a live target.
 *
 * Every controller and queue job that touches a live target routes
 * through this (controllers via this middleware; jobs via
 * Website::isVerified() re-checked at execution time — see Architecture §7).
 */
class EnsureWebsiteVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $website = $request->route('website');

        if ($website instanceof Website && ! $website->isVerified()) {
            abort(403, 'This website must be verified before this action is available.');
        }

        return $next($request);
    }
}
