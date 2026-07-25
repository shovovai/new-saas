<?php

namespace App\Services\AI;

use App\Models\AiReport;
use App\Models\Website;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Turns a raw scan report's findings into a plain-language AI summary
 * (Functional Spec §7): summary, recommendations, risk level, estimated
 * impact, priority.
 */
class ReportSummarizerService
{
    public function __construct(private readonly AiProviderInterface $provider) {}

    public function summarize(Website $website, Model $report, string $reportType): AiReport
    {
        $findings = collect($report->findings ?? []);
        $score = $report->score ?? null;

        $prompt = "Summarize this {$reportType} scan for {$website->domain} (score: ".($score ?? 'n/a').') '.
            "for a non-technical site owner. Findings:\n".$findings->map(
                fn (array $f) => "- [{$f['severity']}] {$f['title']}: {$f['explanation']}"
            )->implode("\n").
            "\n\nRespond with: a one-paragraph summary, a risk level (low/medium/high/critical), ".
            'a short priority (low/medium/high), and up to 3 concrete recommendations.';

        try {
            $summary = $this->provider->complete([
                ['role' => 'user', 'content' => $prompt],
            ]);
            $riskLevel = $this->inferRiskLevel($findings);
        } catch (AiProviderException $e) {
            $summary = 'AI summarization is currently unavailable ('.$e->getMessage().'). '.
                'Raw findings: '.$findings->pluck('title')->implode('; ');
            $riskLevel = $this->inferRiskLevel($findings);
        }

        return $website->aiReports()->create([
            'type' => 'summary',
            'source_report_type' => $reportType,
            'source_report_id' => $report->id,
            'summary' => $summary,
            'recommendations' => $findings->pluck('explanation')->take(3)->values()->all(),
            'risk_level' => $riskLevel,
            'priority' => $riskLevel === 'critical' || $riskLevel === 'high' ? 'high' : 'medium',
            'model_used' => $this->provider->name(),
        ]);
    }

    private function inferRiskLevel(Collection $findings): string
    {
        return match (true) {
            $findings->contains(fn ($f) => $f['severity'] === 'critical') => 'critical',
            $findings->contains(fn ($f) => $f['severity'] === 'warn') => 'medium',
            default => 'low',
        };
    }
}
