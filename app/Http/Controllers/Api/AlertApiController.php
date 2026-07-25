<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Team-level alert preferences reachable via the API key (which
 * represents the whole team, not a specific human) live with a null
 * user_id — the same "team default" row the web UI never writes to
 * directly, kept separate from any individual member's own preferences.
 */
class AlertApiController extends Controller
{
    private const ALERT_TYPES = [
        'site_down', 'ssl_expiring', 'domain_expiring', 'scan_failed', 'scan_completed', 'pentest_completed',
    ];

    private const CHANNELS = ['email', 'sms', 'slack', 'discord', 'telegram', 'push', 'webhook'];

    public function index(Request $request): JsonResponse
    {
        /** @var Team $team */
        $team = $request->attributes->get('api_team');

        $existing = $team->alerts()->whereNull('user_id')->get()->keyBy('alert_type');

        $preferences = collect(self::ALERT_TYPES)->map(fn (string $type) => [
            'alert_type' => $type,
            'channels' => $existing->get($type)?->channels ?? ['email'],
            'enabled' => $existing->get($type)?->enabled ?? true,
        ]);

        return response()->json(['data' => $preferences]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'alert_type' => ['required', 'in:'.implode(',', self::ALERT_TYPES)],
            'channels' => ['required', 'array'],
            'channels.*' => ['in:'.implode(',', self::CHANNELS)],
            'enabled' => ['required', 'boolean'],
        ]);

        /** @var Team $team */
        $team = $request->attributes->get('api_team');

        $alert = Alert::updateOrCreate(
            ['team_id' => $team->id, 'user_id' => null, 'alert_type' => $validated['alert_type']],
            ['channels' => $validated['channels'], 'enabled' => $validated['enabled']],
        );

        return response()->json(['data' => [
            'alert_type' => $alert->alert_type,
            'channels' => $alert->channels,
            'enabled' => $alert->enabled,
        ]]);
    }
}
