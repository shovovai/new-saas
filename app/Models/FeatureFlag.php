<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Global platform-wide kill switch — distinct from the per-plan
 * `plan_features` commercial matrix (App\Models\PlanFeature).
 */
class FeatureFlag extends Model
{
    protected $fillable = ['key', 'label', 'description', 'enabled'];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Any write to a flag — from the admin panel or anywhere else —
        // must take effect immediately, since this is meant to be an
        // instant platform-wide kill switch.
        static::saved(fn (self $flag) => Cache::forget("feature_flag:{$flag->key}"));
    }
}
