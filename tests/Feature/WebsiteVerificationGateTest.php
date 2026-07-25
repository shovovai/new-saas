<?php

namespace Tests\Feature;

use App\Jobs\RunPerformanceScanJob;
use App\Models\MonitoringJob;
use App\Models\Website;
use App\Services\Scanning\PenTestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\SetsUpTeam;
use Tests\TestCase;

/**
 * Covers the non-negotiable rule from 00_BUILD_PROMPT.md: no scan, AI
 * analysis, monitoring job, or pen test may ever run against a website
 * whose status is not `verified` — enforced by EnsureWebsiteVerified at
 * the HTTP layer and re-checked independently inside every queued job,
 * and by the pen test module's separate authorization ledger.
 */
class WebsiteVerificationGateTest extends TestCase
{
    use RefreshDatabase, SetsUpTeam;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCatalog();
        Http::fake(['*' => Http::response('<html><head><title>Test</title></head><body></body></html>', 200, [
            'Strict-Transport-Security' => 'max-age=1',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Content-Security-Policy' => "default-src 'self'",
        ])]);
    }

    private function makeWebsite(string $status, $team, $user): Website
    {
        return $team->websites()->create([
            'created_by' => $user->id,
            'name' => 'Test Site',
            'url' => 'https://example.test',
            'domain' => 'example.test',
            'status' => $status,
        ]);
    }

    public function test_http_layer_blocks_scan_dispatch_for_unverified_website(): void
    {
        [$user, $team] = $this->createUserWithTeam('professional');
        $website = $this->makeWebsite('pending_verification', $team, $user);

        $response = $this->actingAs($user)->post("/websites/{$website->id}/scans/performance");

        $response->assertForbidden();
        $this->assertSame(0, MonitoringJob::count());
    }

    public function test_http_layer_allows_scan_dispatch_for_verified_website_on_a_plan_with_the_feature(): void
    {
        [$user, $team] = $this->createUserWithTeam('professional');
        $website = $this->makeWebsite('verified', $team, $user);
        $website->update(['verified_at' => now(), 'verified_method' => 'meta_tag']);

        $response = $this->actingAs($user)->post("/websites/{$website->id}/scans/performance");

        $response->assertRedirect();
        $this->assertSame(1, MonitoringJob::count());
        $this->assertSame('completed', MonitoringJob::first()->status);
    }

    public function test_scan_job_itself_refuses_to_run_against_an_unverified_website(): void
    {
        // Defense in depth: even if a job were dispatched directly (e.g. a
        // stale queue entry from before the site was un-verified), the job
        // re-checks verification at execution time and must refuse.
        [$user, $team] = $this->createUserWithTeam('professional');
        $website = $this->makeWebsite('pending_verification', $team, $user);

        $monitoringJob = $website->monitoringJobs()->create([
            'dispatched_by' => $user->id,
            'type' => 'performance',
            'status' => 'queued',
        ]);

        app()->call([new RunPerformanceScanJob($website->id, $monitoringJob->id), 'handle']);

        $monitoringJob->refresh();
        $this->assertSame('failed', $monitoringJob->status);
        $this->assertStringContainsString('not verified', $monitoringJob->failure_reason);
        $this->assertSame(0, $website->performanceReports()->count());
    }

    public function test_pentest_is_blocked_when_website_is_unverified_even_with_authorization_and_plan(): void
    {
        [$user, $team] = $this->createUserWithTeam('agency');
        $website = $this->makeWebsite('pending_verification', $team, $user);

        $penTest = app(PenTestService::class);
        $penTest->authorize($website, $user, ['security_headers']);

        $this->assertFalse($penTest->canRun($website, ['security_headers']));
        $this->expectException(\RuntimeException::class);
        $penTest->run($website, ['security_headers'], $user);
    }

    public function test_pentest_is_blocked_without_an_active_authorization_even_when_verified_and_on_plan(): void
    {
        [$user, $team] = $this->createUserWithTeam('agency');
        $website = $this->makeWebsite('verified', $team, $user);
        $website->update(['verified_at' => now(), 'verified_method' => 'meta_tag']);

        $penTest = app(PenTestService::class);

        $this->assertFalse($penTest->canRun($website, ['security_headers']));
    }

    public function test_pentest_authorization_scope_must_cover_every_requested_category(): void
    {
        [$user, $team] = $this->createUserWithTeam('agency');
        $website = $this->makeWebsite('verified', $team, $user);
        $website->update(['verified_at' => now(), 'verified_method' => 'meta_tag']);

        app(PenTestService::class)->authorize($website, $user, ['clickjacking']);

        $penTest = app(PenTestService::class);

        $this->assertTrue($penTest->canRun($website, ['clickjacking']));
        $this->assertFalse($penTest->canRun($website, ['clickjacking', 'sensitive_file_exposure']));
    }

    public function test_pentest_run_writes_an_audit_log_entry(): void
    {
        [$user, $team] = $this->createUserWithTeam('agency');
        $website = $this->makeWebsite('verified', $team, $user);
        $website->update(['verified_at' => now(), 'verified_method' => 'meta_tag']);

        app(PenTestService::class)->authorize($website, $user, ['security_headers']);
        app(PenTestService::class)->run($website, ['security_headers'], $user);

        $this->assertDatabaseHas('audit_logs', ['action' => 'pentest.authorized']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'pentest.run_completed']);
    }

    public function test_a_revoked_pentest_authorization_no_longer_authorizes_new_runs(): void
    {
        [$user, $team] = $this->createUserWithTeam('agency');
        $website = $this->makeWebsite('verified', $team, $user);
        $website->update(['verified_at' => now(), 'verified_method' => 'meta_tag']);

        $penTest = app(PenTestService::class);
        $authorization = $penTest->authorize($website, $user, ['security_headers']);

        $this->assertTrue($penTest->canRun($website, ['security_headers']));

        $penTest->revoke($authorization, $user);

        $this->assertFalse($penTest->canRun($website, ['security_headers']));
    }
}
