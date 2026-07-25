<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AlertPreferenceController extends Controller
{
    private const ALERT_TYPES = [
        'site_down', 'ssl_expiring', 'domain_expiring', 'scan_failed', 'scan_completed', 'pentest_completed',
    ];

    private const CHANNELS = ['email', 'sms', 'slack', 'discord', 'telegram', 'push', 'webhook'];

    public function index(Request $request): Response
    {
        $team = $request->user()->currentTeam;

        $existing = $team->alerts()->where('user_id', $request->user()->id)->get()->keyBy('alert_type');
        $any = $existing->first();

        $preferences = collect(self::ALERT_TYPES)->map(fn (string $type) => [
            'alert_type' => $type,
            'channels' => $existing->get($type)?->channels ?? ['email'],
            'enabled' => $existing->get($type)?->enabled ?? true,
        ]);

        return Inertia::render('Alerts/Index', [
            'preferences' => $preferences,
            'availableChannels' => self::CHANNELS,
            'destinations' => [
                'slack_webhook_url' => $any?->slack_webhook_url,
                'discord_webhook_url' => $any?->discord_webhook_url,
                'telegram_chat_id' => $any?->telegram_chat_id,
                'phone_number' => $any?->phone_number,
                'webhook_url' => $any?->webhook_url,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'alert_type' => ['required', 'in:'.implode(',', self::ALERT_TYPES)],
            'channels' => ['required', 'array'],
            'channels.*' => ['in:'.implode(',', self::CHANNELS)],
            'enabled' => ['required', 'boolean'],
        ]);

        Alert::updateOrCreate(
            [
                'team_id' => $request->user()->current_team_id,
                'user_id' => $request->user()->id,
                'alert_type' => $validated['alert_type'],
            ],
            [
                'channels' => $validated['channels'],
                'enabled' => $validated['enabled'],
            ],
        );

        return back()->with('success', 'Notification preferences updated.');
    }

    /**
     * Slack/Discord/Telegram/SMS/webhook destinations are per-user, not
     * per-alert-type — set once here and applied to every alert type row
     * so each channel only needs to be configured a single time.
     */
    public function updateDestinations(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'slack_webhook_url' => ['nullable', 'url', 'max:255'],
            'discord_webhook_url' => ['nullable', 'url', 'max:255'],
            'telegram_chat_id' => ['nullable', 'string', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:32'],
            'webhook_url' => ['nullable', 'url', 'max:255'],
        ]);

        $team = $request->user()->currentTeam;

        foreach (self::ALERT_TYPES as $type) {
            $alert = Alert::firstOrNew(
                ['team_id' => $team->id, 'user_id' => $request->user()->id, 'alert_type' => $type],
                ['channels' => ['email'], 'enabled' => true],
            );
            $alert->fill($validated)->save();
        }

        return back()->with('success', 'Notification destinations updated.');
    }
}
