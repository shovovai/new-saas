<?php

namespace App\Services\Billing;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Team;
use Illuminate\Support\Facades\DB;

/**
 * Provider-agnostic subscription lifecycle. Actual Stripe/Paddle/SSLCommerz
 * checkout + webhook handling is a future integration — this service
 * manages the local subscription record that FeatureGateService and
 * PlanEnforcementService read from, so the rest of the app never needs to
 * know which payment provider is behind it.
 */
class SubscriptionService
{
    public function changePlan(Team $team, Plan $plan, string $billingCycle = 'monthly'): Subscription
    {
        return DB::transaction(function () use ($team, $plan, $billingCycle) {
            $team->subscription()
                ->whereIn('status', ['active', 'trialing'])
                ->update(['status' => 'canceled', 'cancels_at' => now()]);

            return $team->subscription()->create([
                'plan_id' => $plan->id,
                'status' => 'active',
                'billing_cycle' => $billingCycle,
                'current_period_start' => now(),
                'current_period_end' => $billingCycle === 'yearly' ? now()->addYear() : now()->addMonth(),
            ]);
        });
    }

    public function cancel(Team $team): void
    {
        $team->subscription()
            ->whereIn('status', ['active', 'trialing'])
            ->update(['status' => 'canceled', 'cancels_at' => now()]);
    }
}
