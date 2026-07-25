<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\Website;
use App\Services\FeatureGate\FeatureGateService;
use App\Services\Reports\ReportPdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReportApiController extends Controller
{
    private const RELATIONS = [
        'performance' => 'performanceReports',
        'seo' => 'seoReports',
        'security' => 'securityReports',
        'accessibility' => 'accessibilityReports',
    ];

    public function __construct(
        private readonly FeatureGateService $featureGate,
        private readonly ReportPdfService $pdf,
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var Team $team */
        $team = $request->attributes->get('api_team');

        $rows = $team->websites()->get()->flatMap(function (Website $website) {
            $rows = collect();

            foreach (self::RELATIONS as $type => $relation) {
                foreach ($website->{$relation}()->latest()->limit(5)->get() as $report) {
                    $rows->push([
                        'website_id' => $website->id,
                        'website' => $website->name,
                        'type' => $type,
                        'score' => $report->score,
                        'created_at' => $report->created_at,
                    ]);
                }
            }

            return $rows;
        })->sortByDesc('created_at')->values();

        return response()->json(['data' => $rows]);
    }

    public function exportPdf(Request $request, Website $website, string $type): Response
    {
        $this->authorizeTeam($request, $website);

        if (! in_array($type, ReportPdfService::TYPES, true)) {
            abort(404);
        }

        if (! $this->featureGate->can($website, 'reports.pdf')) {
            return response()->json(['message' => 'PDF export is not included in your current plan.'], 403);
        }

        return $this->pdf->generate($website, $type)->download("siteguardian-{$website->domain}-{$type}.pdf");
    }

    private function authorizeTeam(Request $request, Website $website): void
    {
        if ($website->team_id !== $request->attributes->get('api_team')->id) {
            abort(404);
        }
    }
}
