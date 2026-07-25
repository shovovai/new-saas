<?php

namespace App\Http\Middleware;

use App\Models\Website;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defense in depth alongside the BelongsToTeam model scope: confirms the
 * route-bound tenant-scoped model actually belongs to the acting user's
 * current team before a controller touches it.
 */
class EnsureTeamMembership
{
    public function handle(Request $request, Closure $next): Response
    {
        $website = $request->route('website');
        $user = $request->user();

        if ($website instanceof Website && $user && $website->team_id !== $user->current_team_id) {
            abort(404);
        }

        return $next($request);
    }
}
