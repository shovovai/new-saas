<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\RunAccessibilityScanJob;
use App\Jobs\RunPerformanceScanJob;
use App\Jobs\RunSecurityScanJob;
use App\Jobs\RunSeoScanJob;
use App\Models\Website;
use App\Services\Billing\PlanEnforcementService;
use App\Services\FeatureGate\FeatureGateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScanApiController extends Controller
{
    /**
     * @var array<string, string>
     */
    private const RELATIONS = [
        'performance' => 'performanceReports',
        'seo' => 'seoReports',
        'security' => 'securityReports',
        'accessibility' => 'accessibilityReports',
    ];

    /**
     * @var array<string, class-string>
     */
    private const JOBS = [
        'performance' => RunPerformanceScanJob::class,
        'seo' => RunSeoScanJob::class,
        'security' => RunSecurityScanJob::class,
        'accessibility' => RunAccessibilityScanJob::class,
    ];

    public function __construct(
        private readonly FeatureGateService $featureGate,
        private readonly PlanEnforcementService $planEnforcement,
    ) {}

    public function show(Request $request, Website $website, string $type): JsonResponse
    {
        $this->authorizeTeam($request, $website);
        $relation = self::RELATIONS[$type] ?? abort(404);

        $reports = $website->{$relation}()->latest()->limit(20)->get(['id', 'score', 'findings', 'created_at']);

        return response()->json([
            'data' => [
                'latest' => $reports->first(),
                'history' => $reports,
            ],
        ]);
    }

    public function store(Request $request, Website $website, string $type): JsonResponse
    {
        $this->authorizeTeam($request, $website);
        $jobClass = self::JOBS[$type] ?? abort(404);

        if (! $website->isVerified()) {
            return response()->json(['message' => 'This website is not verified.'], 403);
        }

        if (! $this->featureGate->can($website, "{$type}.scans")) {
            return response()->json(['message' => 'This scan type is not included in your current plan.'], 403);
        }

        if (! $this->planEnforcement->canRunScanNow($website->team)) {
            return response()->json(['message' => 'Monthly scan quota reached.'], 429);
        }

        $monitoringJob = $website->monitoringJobs()->create([
            'type' => $type,
            'status' => 'queued',
        ]);

        $jobClass::dispatch($website->id, $monitoringJob->id);

        return response()->json(['data' => ['monitoring_job_id' => $monitoringJob->id, 'status' => 'queued']], 202);
    }

    private function authorizeTeam(Request $request, Website $website): void
    {
        if ($website->team_id !== $request->attributes->get('api_team')->id) {
            abort(404);
        }
    }
}
