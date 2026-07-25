<?php

namespace Tests\Feature;

use App\Jobs\RunPerformanceScanJob;
use App\Models\ApiKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\SetsUpTeam;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase, SetsUpTeam;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCatalog();
    }

    private function verifiedWebsite($team, $user)
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

    public function test_a_request_without_a_bearer_token_is_rejected(): void
    {
        $this->getJson('/api/v1/websites')->assertUnauthorized();
    }

    public function test_a_malformed_or_unknown_token_is_rejected(): void
    {
        $this->withHeader('Authorization', 'Bearer not-a-real-key')
            ->getJson('/api/v1/websites')
            ->assertUnauthorized();
    }

    public function test_a_valid_key_can_list_and_show_websites_scoped_to_its_own_team(): void
    {
        [$user, $team] = $this->createUserWithTeam('professional');
        $website = $this->verifiedWebsite($team, $user);

        [$otherUser, $otherTeam] = $this->createUserWithTeam('professional');
        $this->verifiedWebsite($otherTeam, $otherUser);

        $result = ApiKey::generate($team, $user, 'CI key');

        $response = $this->withHeader('Authorization', "Bearer {$result['plaintext']}")
            ->getJson('/api/v1/websites');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $website->id);

        $this->withHeader('Authorization', "Bearer {$result['plaintext']}")
            ->getJson("/api/v1/websites/{$website->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $website->id);
    }

    public function test_a_key_cannot_read_a_website_belonging_to_another_team(): void
    {
        [$user, $team] = $this->createUserWithTeam('professional');
        [$otherUser, $otherTeam] = $this->createUserWithTeam('professional');
        $otherWebsite = $this->verifiedWebsite($otherTeam, $otherUser);

        $result = ApiKey::generate($team, $user, 'CI key');

        $this->withHeader('Authorization', "Bearer {$result['plaintext']}")
            ->getJson("/api/v1/websites/{$otherWebsite->id}")
            ->assertNotFound();
    }

    public function test_a_revoked_key_is_rejected(): void
    {
        [$user, $team] = $this->createUserWithTeam('professional');
        $result = ApiKey::generate($team, $user, 'CI key');
        $result['model']->update(['revoked_at' => now()]);

        $this->withHeader('Authorization', "Bearer {$result['plaintext']}")
            ->getJson('/api/v1/websites')
            ->assertUnauthorized();
    }

    public function test_a_key_belonging_to_a_plan_without_api_access_is_rejected(): void
    {
        [$user, $team] = $this->createUserWithTeam('starter');
        $result = ApiKey::generate($team, $user, 'CI key');

        $this->withHeader('Authorization', "Bearer {$result['plaintext']}")
            ->getJson('/api/v1/websites')
            ->assertForbidden();
    }

    public function test_a_key_can_trigger_a_scan_which_dispatches_the_job_and_returns_202(): void
    {
        Queue::fake();

        [$user, $team] = $this->createUserWithTeam('professional');
        $website = $this->verifiedWebsite($team, $user);
        $result = ApiKey::generate($team, $user, 'CI key');

        $response = $this->withHeader('Authorization', "Bearer {$result['plaintext']}")
            ->postJson("/api/v1/websites/{$website->id}/scans/performance");

        $response->assertStatus(202);
        $response->assertJsonStructure(['data' => ['monitoring_job_id', 'status']]);

        Queue::assertPushed(RunPerformanceScanJob::class);
        $this->assertDatabaseHas('monitoring_jobs', [
            'website_id' => $website->id,
            'type' => 'performance',
            'status' => 'queued',
        ]);
    }

    public function test_triggering_a_scan_on_an_unverified_website_is_forbidden(): void
    {
        [$user, $team] = $this->createUserWithTeam('professional');
        $website = $team->websites()->create([
            'created_by' => $user->id,
            'name' => 'Unverified',
            'url' => 'https://unverified.test',
            'domain' => 'unverified.test',
            'status' => 'pending',
        ]);
        $result = ApiKey::generate($team, $user, 'CI key');

        $this->withHeader('Authorization', "Bearer {$result['plaintext']}")
            ->postJson("/api/v1/websites/{$website->id}/scans/performance")
            ->assertForbidden();
    }

    public function test_reports_index_lists_recent_scores_across_team_websites(): void
    {
        [$user, $team] = $this->createUserWithTeam('professional');
        $website = $this->verifiedWebsite($team, $user);
        $website->performanceReports()->create(['score' => 91, 'findings' => []]);
        $result = ApiKey::generate($team, $user, 'CI key');

        $response = $this->withHeader('Authorization', "Bearer {$result['plaintext']}")
            ->getJson('/api/v1/reports');

        $response->assertOk();
        $response->assertJsonPath('data.0.score', 91);
    }

    public function test_reports_pdf_export_downloads_a_real_pdf(): void
    {
        [$user, $team] = $this->createUserWithTeam('professional');
        $website = $this->verifiedWebsite($team, $user);
        $website->performanceReports()->create(['score' => 91, 'findings' => []]);
        $result = ApiKey::generate($team, $user, 'CI key');

        $response = $this->withHeader('Authorization', "Bearer {$result['plaintext']}")
            ->get("/api/v1/websites/{$website->id}/reports/executive.pdf");

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_alert_preferences_can_be_read_and_updated_via_the_api(): void
    {
        [$user, $team] = $this->createUserWithTeam('professional');
        $result = ApiKey::generate($team, $user, 'CI key');

        $this->withHeader('Authorization', "Bearer {$result['plaintext']}")
            ->getJson('/api/v1/alerts')
            ->assertOk()
            ->assertJsonCount(6, 'data');

        $response = $this->withHeader('Authorization', "Bearer {$result['plaintext']}")
            ->putJson('/api/v1/alerts', [
                'alert_type' => 'site_down',
                'channels' => ['email', 'slack'],
                'enabled' => false,
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('alerts', [
            'team_id' => $team->id,
            'user_id' => null,
            'alert_type' => 'site_down',
            'enabled' => false,
        ]);
    }

    public function test_requests_are_rate_limited_per_key(): void
    {
        [$user, $team] = $this->createUserWithTeam('professional');
        $result = ApiKey::generate($team, $user, 'CI key');

        for ($i = 0; $i < 60; $i++) {
            $this->withHeader('Authorization', "Bearer {$result['plaintext']}")
                ->getJson('/api/v1/websites')
                ->assertOk();
        }

        $this->withHeader('Authorization', "Bearer {$result['plaintext']}")
            ->getJson('/api/v1/websites')
            ->assertStatus(429);
    }
}
