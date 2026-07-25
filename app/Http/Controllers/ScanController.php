<?php

namespace App\Http\Controllers;

use App\Jobs\RunAccessibilityScanJob;
use App\Jobs\RunPerformanceScanJob;
use App\Jobs\RunSecurityScanJob;
use App\Jobs\RunSeoScanJob;
use App\Models\Website;
use App\Services\Billing\PlanEnforcementService;
use App\Services\FeatureGate\FeatureGateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ScanController extends Controller
{
    /**
     * @var array<string, class-string>
     */
    private const JOBS = [
        'performance' => RunPerformanceScanJob::class,
        'seo' => RunSeoScanJob::class,
        'security' => RunSecurityScanJob::class,
        'accessibility' => RunAccessibilityScanJob::class,
    ];

    /**
     * @var array<string, string>
     */
    private const REPORT_RELATIONS = [
        'performance' => 'performanceReports',
        'seo' => 'seoReports',
        'security' => 'securityReports',
        'accessibility' => 'accessibilityReports',
    ];

    public function __construct(
        private readonly FeatureGateService $featureGate,
        private readonly PlanEnforcementService $planEnforcement,
    ) {}

    public function show(Request $request, Website $website, string $type): Response
    {
        $relation = self::REPORT_RELATIONS[$type] ?? abort(404);

        $reports = $website->{$relation}()->latest()->limit(20)->get();

        return Inertia::render('Scans/Show', [
            'website' => $website,
            'type' => $type,
            'latest' => $reports->first(),
            'history' => $reports,
            'canRunScan' => $website->isVerified()
                && $this->featureGate->can($website, "{$type}.scans")
                && $this->planEnforcement->canRunScanNow($website->team),
            'quotaRemaining' => $this->planEnforcement->remainingScansThisMonth($website->team),
        ]);
    }

    public function store(Request $request, Website $website, string $type): RedirectResponse
    {
        $jobClass = self::JOBS[$type] ?? abort(404);
        $team = $website->team;

        if (! $this->featureGate->can($website, "{$type}.scans")) {
            abort(403, 'This scan type is not included in your current plan.');
        }

        if (! $this->planEnforcement->canRunScanNow($team)) {
            return back()->with('error', 'Your plan\'s monthly scan quota has been reached. Upgrade to run more scans.');
        }

        $monitoringJob = $website->monitoringJobs()->create([
            'dispatched_by' => Auth::id(),
            'type' => $type,
            'status' => 'queued',
        ]);

        $jobClass::dispatch($website->id, $monitoringJob->id);

        return back()->with('success', ucfirst($type).' scan queued.');
    }
}
