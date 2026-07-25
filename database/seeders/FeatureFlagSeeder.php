<?php

namespace Database\Seeders;

use App\Models\FeatureFlag;
use Illuminate\Database\Seeder;

class FeatureFlagSeeder extends Seeder
{
    /**
     * Global kill-switches, all on by default. These gate whole subsystems
     * platform-wide (e.g. during an outage or incident) and are distinct
     * from the per-plan `plan_features` commercial matrix.
     */
    public function run(): void
    {
        $flags = [
            'ai.assistant' => 'AI Assistant (platform-wide)',
            'ai.report_summarization' => 'AI Report Summarization (platform-wide)',
            'ai.fix_generation' => 'AI Fix Generation (platform-wide)',
            'pentest.module' => 'Penetration Testing Module (platform-wide)',
            'monitoring.core' => 'Monitoring Engine (platform-wide)',
            'performance.scans' => 'Performance Scans (platform-wide)',
            'seo.scans' => 'SEO Scans (platform-wide)',
            'security.scans' => 'Security Scans (platform-wide)',
            'accessibility.scans' => 'Accessibility Scans (platform-wide)',
        ];

        foreach ($flags as $key => $label) {
            FeatureFlag::firstOrCreate(['key' => $key], ['label' => $label, 'enabled' => true]);
        }
    }
}
