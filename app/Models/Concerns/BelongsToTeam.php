<?php

namespace App\Models\Concerns;

use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Enforces the Team tenancy boundary at the model layer (Architecture §8):
 * every tenant-scoped model is automatically scoped to the acting user's
 * team, so a controller forgetting a ->where('team_id', ...) clause cannot
 * leak another tenant's rows.
 */
trait BelongsToTeam
{
    public static function bootBelongsToTeam(): void
    {
        static::addGlobalScope('team', function (Builder $builder) {
            if (auth()->check() && auth()->user()->current_team_id) {
                $builder->where($builder->getModel()->getTable().'.team_id', auth()->user()->current_team_id);
            }
        });

        static::creating(function ($model) {
            if (! $model->team_id && auth()->check()) {
                $model->team_id = auth()->user()->current_team_id;
            }
        });
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
