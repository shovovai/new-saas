<?php

namespace Tests\Feature;

use App\Services\Scanning\PerformanceScanServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Tests\Concerns\SetsUpTeam;
use Tests\TestCase;

class PerformanceScanServiceTest extends TestCase
{
    use RefreshDatabase, SetsUpTeam;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCatalog();
    }

    public function test_it_persists_real_core_web_vitals_from_the_browser_scan(): void
    {
        Process::fake([
            '*' => Process::result(output: json_encode([
                'status' => 200,
                'ttfb_ms' => 120,
                'lcp_ms' => 1800,
                'cls' => 0.05,
                'tbt_ms' => 100,
            ])),
        ]);

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

        $report = app(PerformanceScanServiceInterface::class)->run($website);

        $this->assertSame(120, $report->ttfb_ms);
        $this->assertSame(1800, $report->lcp_ms);
        $this->assertEqualsWithDelta(0.05, $report->cls, 0.001);
        $this->assertSame(100, $report->inp_ms);
        $this->assertGreaterThan(80, $report->score);
    }

    public function test_it_records_a_finding_and_null_score_when_the_browser_scan_fails(): void
    {
        Process::fake([
            '*' => Process::result(output: json_encode(['error' => 'net::ERR_NAME_NOT_RESOLVED'])),
        ]);

        [$user, $team] = $this->createUserWithTeam('professional');
        $website = $team->websites()->create([
            'created_by' => $user->id,
            'name' => 'Test',
            'url' => 'https://unreachable.test',
            'domain' => 'unreachable.test',
            'status' => 'verified',
            'verified_at' => now(),
            'verified_method' => 'meta_tag',
        ]);

        $report = app(PerformanceScanServiceInterface::class)->run($website);

        $this->assertNull($report->score);
        $this->assertSame('availability', $report->findings[0]['category']);
    }
}
