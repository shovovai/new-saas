<?php

namespace Tests\Feature;

use App\Models\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpTeam;
use Tests\TestCase;

class ReportExportTest extends TestCase
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

    public function test_pdf_export_is_forbidden_when_plan_lacks_the_feature(): void
    {
        [$user, $team] = $this->createUserWithTeam('starter');
        $website = $this->verifiedWebsite($team, $user);

        // Explicitly disable reports.pdf for this plan to exercise the
        // gate — every seeded plan includes it by default.
        $feature = Feature::where('key', 'reports.pdf')->firstOrFail();
        $team->activeSubscription()->plan->features()->updateExistingPivot($feature->id, ['enabled' => false]);

        $response = $this->actingAs($user)->get("/websites/{$website->id}/reports/executive.pdf");

        $response->assertForbidden();
    }

    public function test_pdf_export_downloads_a_real_pdf_when_plan_includes_the_feature(): void
    {
        [$user, $team] = $this->createUserWithTeam('professional');
        $website = $this->verifiedWebsite($team, $user);
        $website->performanceReports()->create([
            'score' => 80,
            'findings' => [['category' => 'lcp', 'severity' => 'warn', 'title' => 'Slow LCP', 'explanation' => 'x']],
        ]);

        $response = $this->actingAs($user)->get("/websites/{$website->id}/reports/executive.pdf");

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_csv_export_is_forbidden_when_plan_lacks_the_feature(): void
    {
        [$user, $team] = $this->createUserWithTeam('starter');

        $response = $this->actingAs($user)->get('/reports/export.csv');

        $response->assertForbidden();
    }
}
