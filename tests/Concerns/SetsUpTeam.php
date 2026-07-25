<?php

namespace Tests\Concerns;

use App\Models\Plan;
use App\Models\Team;
use App\Models\User;
use App\Services\Billing\SubscriptionService;
use App\Services\Teams\TeamService;
use Database\Seeders\FeatureFlagSeeder;
use Database\Seeders\FeatureSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\PlanSeeder;

trait SetsUpTeam
{
    protected function seedCatalog(): void
    {
        (new PermissionSeeder)->run();
        (new FeatureSeeder)->run();
        (new PlanSeeder)->run();
        (new FeatureFlagSeeder)->run();
    }

    /**
     * @return array{0: User, 1: Team}
     */
    protected function createUserWithTeam(string $planSlug = 'starter'): array
    {
        $user = User::factory()->create();
        $team = app(TeamService::class)->createPersonalTeam($user);

        if ($planSlug !== 'starter') {
            $plan = Plan::query()->where('slug', $planSlug)->firstOrFail();
            app(SubscriptionService::class)->changePlan($team, $plan);
        }

        return [$user->fresh(), $team->fresh()];
    }
}
