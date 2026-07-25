<?php

namespace App\Services\Reports;

use App\Models\Website;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfDocument;

/**
 * Real PDF generation via DomPDF (Functional Spec §10: Executive,
 * Developer, SEO, Performance, Security, Pen Testing, AI report types).
 * "Executive" and "Developer" are both a combined view across every scan
 * type — Executive summarizes scores only, Developer includes every
 * finding's full explanation.
 */
class ReportPdfService
{
    /**
     * @var list<string>
     */
    public const TYPES = ['executive', 'developer', 'performance', 'seo', 'security', 'accessibility', 'pentest'];

    public function generate(Website $website, string $type): PdfDocument
    {
        [$title, $sections] = match ($type) {
            'executive' => ['Executive Summary', $this->executiveSections($website, detailed: false)],
            'developer' => ['Developer Report', $this->executiveSections($website, detailed: true)],
            'performance' => ['Performance Report', [$this->scanSection($website->performanceReports()->latest()->first(), 'Performance')]],
            'seo' => ['SEO Report', [$this->scanSection($website->seoReports()->latest()->first(), 'SEO')]],
            'security' => ['Security Report', [$this->scanSection($website->securityReports()->latest()->first(), 'Security')]],
            'accessibility' => ['Accessibility Report', [$this->scanSection($website->accessibilityReports()->latest()->first(), 'Accessibility')]],
            'pentest' => ['Penetration Test Report', $this->pentestSections($website)],
            default => throw new \InvalidArgumentException("Unknown report type \"{$type}\"."),
        };

        return Pdf::loadView('reports.pdf', [
            'title' => $title,
            'website' => $website,
            'sections' => array_values(array_filter($sections)),
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function executiveSections(Website $website, bool $detailed): array
    {
        $sections = [];

        foreach ([
            'Performance' => $website->performanceReports()->latest()->first(),
            'SEO' => $website->seoReports()->latest()->first(),
            'Security' => $website->securityReports()->latest()->first(),
            'Accessibility' => $website->accessibilityReports()->latest()->first(),
        ] as $label => $report) {
            if (! $report) {
                continue;
            }

            $sections[] = [
                'title' => $label,
                'score' => $report->score,
                'findings' => $detailed ? ($report->findings ?? []) : [],
            ];
        }

        $latestAi = $website->aiReports()->latest()->first();

        if ($latestAi) {
            $sections[] = [
                'title' => 'AI Summary',
                'summary' => $latestAi->summary,
            ];
        }

        return $sections;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function pentestSections(Website $website): array
    {
        $reports = $website->penTestReports()->latest()->limit(5)->get();

        return $reports->map(fn ($report) => [
            'title' => 'Run '.$report->created_at->toDayDateTimeString().' — risk: '.$report->risk_level,
            'findings' => $report->findings ?? [],
        ])->all();
    }

    private function scanSection($report, string $label): ?array
    {
        if (! $report) {
            return null;
        }

        return [
            'title' => $label,
            'score' => $report->score,
            'findings' => $report->findings ?? [],
        ];
    }
}
