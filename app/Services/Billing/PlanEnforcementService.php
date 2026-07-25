<?php

namespace App\Services\Billing;

use App\Models\MonitoringJob;
use App\Models\Team;
use App\Services\FeatureGate\FeatureGateService;
use Carbon\CarbonImmutable;

/**
 * Enforces the numeric limits on a plan (max_websites, max_team_members,
 * max_scans_per_month) — separate from FeatureGateService, which answers
 * "is this feature available at all" rather than "how many are left".
 */
class PlanEnforcementService
{
    public function __construct(private readonly FeatureGateService $featureGate) {}

    public function canAddWebsite(Team $team): bool
    {
        $plan = $this->featureGate->planFor($team);

        if (! $plan) {
            return false;
        }

        return $team->websites()->count() < $plan->max_websites;
    }

    public function canAddTeamMember(Team $team): bool
    {
        $plan = $this->featureGate->planFor($team);

        if (! $plan) {
            return false;
        }

        return $team->members()->count() < $plan->max_team_members;
    }

    public function remainingWebsiteSlots(Team $team): int
    {
        $plan = $this->featureGate->planFor($team);

        if (! $plan) {
            return 0;
        }

        return max(0, $plan->max_websites - $team->websites()->count());
    }

    public function remainingScansThisMonth(Team $team): int
    {
        $plan = $this->featureGate->planFor($team);

        if (! $plan) {
            return 0;
        }

        $usedThisMonth = MonitoringJob::query()
            ->whereHas('website', fn ($q) => $q->where('team_id', $team->id))
            ->where('created_at', '>=', CarbonImmutable::now()->startOfMonth())
            ->count();

        return max(0, $plan->max_scans_per_month - $usedThisMonth);
    }

    public function canRunScanNow(Team $team): bool
    {
        return $this->remainingScansThisMonth($team) > 0;
    }
}
