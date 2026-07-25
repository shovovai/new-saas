<?php

namespace App\Services\FeatureGate;

use App\Models\Feature;
use App\Models\FeatureFlag;
use App\Models\Plan;
use App\Models\Team;
use App\Models\Website;
use Illuminate\Support\Facades\Cache;

/**
 * The soft, plan-driven gate (Architecture §2): does the team's current
 * plan include this feature. This is a *commercial* boundary only — it
 * never checks whether a website is verified. That is the separate, hard
 * security boundary enforced by EnsureWebsiteVerified middleware, and the
 * two must never be merged (a paying customer with an unverified site
 * must still be blocked by that other gate).
 *
 * Every call site should route through here rather than ever writing
 * `if ($user->plan === 'pro')` — the plan_features table is the single
 * source of truth and is editable from the admin panel with zero deploys.
 */
class FeatureGateService
{
    public function can(Website $website, string $featureKey): bool
    {
        return $this->canForTeam($website->team, $featureKey);
    }

    public function canForTeam(Team $team, string $featureKey): bool
    {
        if (! $this->isGloballyEnabled($featureKey)) {
            return false;
        }

        $plan = $this->planFor($team);

        if (! $plan) {
            return false;
        }

        return $plan->features()
            ->where('features.key', $featureKey)
            ->wherePivot('enabled', true)
            ->exists();
    }

    /**
     * Optional numeric cap attached to a feature on the team's plan
     * (e.g. "ai.use" limited to N requests). Null means uncapped.
     */
    public function limitFor(Team $team, string $featureKey): ?int
    {
        $plan = $this->planFor($team);

        if (! $plan) {
            return 0;
        }

        $pivot = $plan->features()
            ->where('features.key', $featureKey)
            ->first()
            ?->pivot;

        return $pivot?->limit;
    }

    /**
     * @return list<string> feature keys enabled for the team's current plan
     */
    public function enabledFeatureKeysForTeam(Team $team): array
    {
        $plan = $this->planFor($team);

        if (! $plan) {
            return [];
        }

        return $plan->features()
            ->wherePivot('enabled', true)
            ->pluck('features.key')
            ->all();
    }

    public function isGloballyEnabled(string $featureKey): bool
    {
        return Cache::remember("feature_flag:{$featureKey}", 60, function () use ($featureKey) {
            $flag = FeatureFlag::query()->where('key', $featureKey)->first();

            // No explicit global kill-switch row means the flag simply
            // isn't platform-gated — defer entirely to the plan matrix.
            return $flag?->enabled ?? true;
        });
    }

    public function planFor(Team $team): ?Plan
    {
        $subscription = $team->subscription()
            ->whereIn('status', ['active', 'trialing'])
            ->latest()
            ->first();

        return $subscription?->plan;
    }
}
