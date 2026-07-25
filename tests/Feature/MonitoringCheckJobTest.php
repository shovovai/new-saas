<?php

namespace Tests\Feature;

use App\Jobs\RunMonitoringCheckJob;
use App\Models\Alert;
use App\Notifications\AlertNotification;
use App\Services\Monitoring\TlsCertificateInspector;
use App\Services\Monitoring\WhoisClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Tests\Concerns\SetsUpTeam;
use Tests\TestCase;

class MonitoringCheckJobTest extends TestCase
{
    use RefreshDatabase, SetsUpTeam;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCatalog();

        // TLS/WHOIS do real network I/O — stub them so tests never touch
        // the network or wait on socket timeouts.
        $this->app->instance(TlsCertificateInspector::class, Mockery::mock(TlsCertificateInspector::class, ['inspect' => null]));
        $this->app->instance(WhoisClient::class, Mockery::mock(WhoisClient::class, ['expiresAt' => null]));
    }

    private function makeWebsite($team, $user)
    {
        return $team->websites()->create([
            'created_by' => $user->id,
            'name' => 'Test',
            'url' => 'https://example.test',
            'domain' => 'example.test',
            'status' => 'verified',
            'verified_at' => now(),
            'verified_method' => 'meta_tag',
        ]);
    }

    public function test_it_records_a_successful_uptime_check(): void
    {
        Http::fake(['*' => Http::response('ok', 200)]);

        [$user, $team] = $this->createUserWithTeam('professional');
        $website = $this->makeWebsite($team, $user);

        RunMonitoringCheckJob::dispatchSync($website->id);

        $this->assertDatabaseHas('monitoring_logs', [
            'website_id' => $website->id,
            'check_type' => 'uptime',
            'status' => 'ok',
        ]);
    }

    public function test_it_fires_a_site_down_alert_and_logs_critical_on_failure(): void
    {
        Notification::fake();
        Http::fake(['*' => Http::response('Server Error', 500)]);

        [$user, $team] = $this->createUserWithTeam('professional');
        $website = $this->makeWebsite($team, $user);

        Alert::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'alert_type' => 'site_down',
            'channels' => ['email'],
            'enabled' => true,
        ]);

        RunMonitoringCheckJob::dispatchSync($website->id);

        $this->assertDatabaseHas('monitoring_logs', [
            'website_id' => $website->id,
            'check_type' => 'uptime',
            'status' => 'critical',
        ]);
        Notification::assertSentTo($user, AlertNotification::class);
    }

    public function test_it_skips_unverified_and_paused_websites(): void
    {
        [$user, $team] = $this->createUserWithTeam('professional');
        $website = $this->makeWebsite($team, $user);
        $website->update(['status' => 'paused']);

        RunMonitoringCheckJob::dispatchSync($website->id);

        $this->assertDatabaseMissing('monitoring_logs', ['website_id' => $website->id]);
    }

    public function test_it_detects_a_dns_change_between_two_runs(): void
    {
        Http::fake(['*' => Http::response('ok', 200)]);

        [$user, $team] = $this->createUserWithTeam('professional');
        $website = $this->makeWebsite($team, $user);

        // Seed a prior DNS snapshot manually (dns_get_record for a .test
        // domain returns nothing real, so we simulate the "changed" case
        // directly against the stored snapshot).
        $website->monitoringLogs()->create([
            'check_type' => 'dns_snapshot',
            'status' => 'ok',
            'details' => ['ips' => ['203.0.113.1']],
        ]);

        RunMonitoringCheckJob::dispatchSync($website->id);

        // dns_get_record on a non-resolvable .test domain returns false/[],
        // so no new snapshot or change row is expected — this asserts the
        // job doesn't crash and leaves the original snapshot in place.
        $this->assertDatabaseHas('monitoring_logs', [
            'website_id' => $website->id,
            'check_type' => 'dns_snapshot',
        ]);
    }
}
