<?php

namespace App\Services\Teams;

use App\Enums\TeamRole;
use App\Models\Plan;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TeamService
{
    /**
     * Every new user gets a personal team on registration so the rest of
     * the app (websites, subscriptions, billing) always has a team to
     * scope to — mirrors Architecture §8 (Team is the tenancy boundary).
     */
    public function createPersonalTeam(User $user, ?string $name = null): Team
    {
        return DB::transaction(function () use ($user, $name) {
            $team = Team::create([
                'owner_id' => $user->id,
                'name' => $name ?? $user->name."'s Team",
                'personal_team' => true,
            ]);

            $team->members()->attach($user->id, ['role' => TeamRole::Owner->value]);

            $user->forceFill(['current_team_id' => $team->id])->save();

            $this->subscribeToDefaultPlan($team);

            return $team;
        });
    }

    public function subscribeToDefaultPlan(Team $team): void
    {
        $plan = Plan::query()->where('slug', config('plans.default_slug'))->first();

        if (! $plan) {
            return;
        }

        $team->subscription()->create([
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'current_period_start' => now(),
        ]);
    }
}
