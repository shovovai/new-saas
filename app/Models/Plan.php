<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'name', 'slug', 'description',
        'price_monthly', 'price_yearly',
        'max_websites', 'max_team_members', 'max_scans_per_month', 'scan_frequency',
        'ai_credits', 'storage_mb', 'is_active', 'sort_order',
        'stripe_price_id_monthly', 'stripe_price_id_yearly',
        'paddle_price_id_monthly', 'paddle_price_id_yearly',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function planFeatures(): HasMany
    {
        return $this->hasMany(PlanFeature::class);
    }

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(Feature::class, 'plan_features')
            ->withPivot('enabled', 'limit')
            ->withTimestamps();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
