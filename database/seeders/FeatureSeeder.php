<?php

namespace Database\Seeders;

use App\Models\Feature;
use Illuminate\Database\Seeder;

class FeatureSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('features') as $key => $meta) {
            Feature::updateOrCreate(
                ['key' => $key],
                ['name' => $meta['name'], 'category' => $meta['category'] ?? null],
            );
        }
    }
}
