<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\Teams\TeamService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            FeatureSeeder::class,
            PlanSeeder::class,
            FeatureFlagSeeder::class,
        ]);

        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        app(TeamService::class)->createPersonalTeam($user);
    }
}
