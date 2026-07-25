<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Services\FeatureGate\FeatureGateService;
use App\Services\Reports\ReportPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ReportsController extends Controller
{
    public function __construct(
        private readonly FeatureGateService $featureGate,
        private readonly ReportPdfService $pdf,
    ) {}

    public function index(Request $request): InertiaResponse
    {
        $team = $request->user()->currentTeam;

        $websites = $team->websites()->get();

        $reports = $websites->flatMap(function ($website) {
            $rows = collect();

            foreach (['performance' => 'performanceReports', 'seo' => 'seoReports', 'security' => 'securityReports', 'accessibility' => 'accessibilityReports'] as $type => $relation) {
                foreach ($website->{$relation}()->latest()->limit(5)->get() as $report) {
                    $rows->push([
                        'website' => $website->name,
                        'website_id' => $website->id,
                        'type' => $type,
                        'score' => $report->score,
                        'created_at' => $report->created_at,
                    ]);
                }
            }

            return $rows;
        })->sortByDesc('created_at')->values();

        return Inertia::render('Reports/Index', [
            'reports' => $reports,
            'websites' => $websites->map->only(['id', 'name']),
            'canExportCsv' => $this->featureGate->canForTeam($team, 'reports.csv'),
            'canExportPdf' => $this->featureGate->canForTeam($team, 'reports.pdf'),
        ]);
    }

    public function exportCsv(Request $request)
    {
        $team = $request->user()->currentTeam;

        if (! $this->featureGate->canForTeam($team, 'reports.csv')) {
            abort(403, 'CSV export is not included in your current plan.');
        }

        $websites = $team->websites()->get();

        return Response::streamDownload(function () use ($websites) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['website', 'type', 'score', 'scanned_at']);

            foreach ($websites as $website) {
                foreach (['performance' => 'performanceReports', 'seo' => 'seoReports', 'security' => 'securityReports', 'accessibility' => 'accessibilityReports'] as $type => $relation) {
                    foreach ($website->{$relation}()->latest()->get() as $report) {
                        fputcsv($out, [$website->name, $type, $report->score, $report->created_at]);
                    }
                }
            }

            fclose($out);
        }, 'siteguardian-reports.csv');
    }

    public function exportPdf(Request $request, Website $website, string $type)
    {
        if (! in_array($type, ReportPdfService::TYPES, true)) {
            abort(404);
        }

        if (! $this->featureGate->can($website, 'reports.pdf')) {
            abort(403, 'PDF export is not included in your current plan.');
        }

        $filename = "siteguardian-{$website->domain}-{$type}.pdf";

        return $this->pdf->generate($website, $type)->download($filename);
    }
}
