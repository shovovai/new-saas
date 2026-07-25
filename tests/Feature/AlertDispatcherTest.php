<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Notifications\AlertNotification;
use App\Services\Notifications\AlertDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\SetsUpTeam;
use Tests\TestCase;

class AlertDispatcherTest extends TestCase
{
    use RefreshDatabase, SetsUpTeam;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCatalog();
    }

    public function test_it_sends_to_every_enabled_channel_and_ignores_disabled_preferences(): void
    {
        Notification::fake();
        Http::fake(['*' => Http::response('ok', 200)]);

        [$user, $team] = $this->createUserWithTeam('professional');
        $website = $team->websites()->create([
            'created_by' => $user->id,
            'name' => 'Test',
            'url' => 'https://example.test',
            'domain' => 'example.test',
            'status' => 'verified',
            'verified_at' => now(),
            'verified_method' => 'meta_tag',
        ]);

        Alert::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'alert_type' => 'site_down',
            'channels' => ['email', 'slack', 'webhook'],
            'slack_webhook_url' => 'https://hooks.slack.com/services/test',
            'webhook_url' => 'https://example.com/hook',
            'enabled' => true,
        ]);

        Alert::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'alert_type' => 'domain_expiring',
            'channels' => ['email'],
            'enabled' => false,
        ]);

        app(AlertDispatcher::class)->notify($website, 'site_down', 'Site is down', 'example.test is not responding.');

        Notification::assertSentTo($user, AlertNotification::class);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'hooks.slack.com'));
        Http::assertSent(fn ($request) => $request->url() === 'https://example.com/hook');

        // The disabled preference for a different alert_type must not fire.
        app(AlertDispatcher::class)->notify($website, 'domain_expiring', 'Domain expiring', 'example.test expires soon.');
        Notification::assertSentTimes(AlertNotification::class, 1);
    }
}
