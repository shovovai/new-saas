<?php

namespace Tests\Feature;

use App\Models\FeatureFlag;
use App\Models\Website;
use App\Services\Billing\PlanEnforcementService;
use App\Services\FeatureGate\FeatureGateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpTeam;
use Tests\TestCase;

/**
 * Covers the non-negotiable rule from 00_BUILD_PROMPT.md: every feature is
 * plan-driven, never hardcoded — FeatureGateService::can() must be the
 * single source of truth, and a global feature_flags kill switch must be
 * able to override even a plan that includes the feature.
 */
class FeatureGateTest extends TestCase
{
    use RefreshDatabase, SetsUpTeam;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCatalog();
    }

    private function verifiedWebsite($team, $user): Website
    {
        return $team->websites()->create([
            'created_by' => $user->id,
            'name' => 'Test Site',
            'url' => 'https://example.test',
            'domain' => 'example.test',
            'status' => 'verified',
            'verified_at' => now(),
            'verified_method' => 'meta_tag',
        ]);
    }

    public function test_feature_not_included_in_plan_is_denied(): void
    {
        [$user, $team] = $this->createUserWithTeam('starter');
        $website = $this->verifiedWebsite($team, $user);

        // Starter does not include the AI assistant.
        $this->assertFalse(app(FeatureGateService::class)->can($website, 'ai.assistant'));
    }

    public function test_feature_included_in_plan_is_allowed(): void
    {
        [$user, $team] = $this->createUserWithTeam('professional');
        $website = $this->verifiedWebsite($team, $user);

        $this->assertTrue(app(FeatureGateService::class)->can($website, 'ai.assistant'));
    }

    public function test_global_kill_switch_overrides_a_plan_that_includes_the_feature(): void
    {
        [$user, $team] = $this->createUserWithTeam('professional');
        $website = $this->verifiedWebsite($team, $user);

        $this->assertTrue(app(FeatureGateService::class)->can($website, 'ai.assistant'));

        FeatureFlag::query()->where('key', 'ai.assistant')->first()->update(['enabled' => false]);

        $this->assertFalse(app(FeatureGateService::class)->can($website, 'ai.assistant'));
    }

    public function test_a_paying_plan_does_not_bypass_the_separate_verification_gate(): void
    {
        // The FeatureGateService only answers the commercial question. Even
        // though the plan includes the feature, an unverified website must
        // still be blocked — but that block lives in EnsureWebsiteVerified /
        // the scan jobs, never merged into this service.
        [$user, $team] = $this->createUserWithTeam('professional');
        $website = $team->websites()->create([
            'created_by' => $user->id,
            'name' => 'Unverified',
            'url' => 'https://unverified.test',
            'domain' => 'unverified.test',
            'status' => 'pending_verification',
        ]);

        $this->assertTrue(app(FeatureGateService::class)->can($website, 'performance.scans'));
        $this->assertFalse($website->isVerified());
    }

    public function test_ai_assistant_route_is_forbidden_when_plan_lacks_the_feature(): void
    {
        [$user, $team] = $this->createUserWithTeam('starter');
        $website = $this->verifiedWebsite($team, $user);

        $response = $this->actingAs($user)->post("/websites/{$website->id}/ai/messages", [
            'message' => 'why is my site slow?',
        ]);

        $response->assertForbidden();
    }

    public function test_plan_enforcement_blocks_adding_a_website_past_the_plan_limit(): void
    {
        [$user, $team] = $this->createUserWithTeam('starter'); // max_websites = 1
        $enforcement = app(PlanEnforcementService::class);

        $this->assertTrue($enforcement->canAddWebsite($team));

        $this->verifiedWebsite($team, $user);

        $this->assertFalse($enforcement->canAddWebsite($team));

        $response = $this->actingAs($user)->post('/websites', ['url' => 'https://another.test']);

        $response->assertSessionHasErrors('url');
    }

    public function test_enabled_feature_keys_for_team_reflects_the_plan_matrix(): void
    {
        [$user, $team] = $this->createUserWithTeam('starter');

        $keys = app(FeatureGateService::class)->enabledFeatureKeysForTeam($team);

        $this->assertContains('monitoring.core', $keys);
        $this->assertNotContains('pentest.module', $keys);
    }
}
