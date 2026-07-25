<?php

namespace Database\Seeders;

use App\Models\Feature;
use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $allFeatureKeys = Feature::query()->pluck('id', 'key');

        foreach (config('plans.plans') as $definition) {
            $features = $definition['features'];
            unset($definition['features']);

            $plan = Plan::updateOrCreate(['slug' => $definition['slug']], $definition);

            $keys = $features === '*' ? $allFeatureKeys->keys()->all() : $features;

            $sync = [];
            foreach ($keys as $key) {
                if ($allFeatureKeys->has($key)) {
                    $sync[$allFeatureKeys[$key]] = ['enabled' => true];
                }
            }

            $plan->features()->sync($sync);
        }
    }
}
