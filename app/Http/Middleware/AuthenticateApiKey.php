<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use App\Services\FeatureGate\FeatureGateService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates a team-level API key (Functional Spec §14: "Team-level
 * API keys, gated by plan's api_access feature"). Not Sanctum's personal
 * access tokens — a request here represents the team, not a specific
 * human user, matching how ApiKeyController generates keys.
 */
class AuthenticateApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token || ! str_starts_with($token, 'sgai_')) {
            return response()->json(['message' => 'Missing or malformed API key.'], 401);
        }

        $candidates = ApiKey::query()
            ->where('key_prefix', substr($token, 0, 12))
            ->whereNull('revoked_at')
            ->get();

        $apiKey = $candidates->first(fn (ApiKey $key) => Hash::check($token, $key->key_hash));

        if (! $apiKey) {
            return response()->json(['message' => 'Invalid or revoked API key.'], 401);
        }

        if (! app(FeatureGateService::class)->canForTeam($apiKey->team, 'api.access')) {
            return response()->json(['message' => 'API access is not included in this team\'s current plan.'], 403);
        }

        $apiKey->update(['last_used_at' => now()]);

        $request->attributes->set('api_key', $apiKey);
        $request->attributes->set('api_team', $apiKey->team);

        return $next($request);
    }
}
