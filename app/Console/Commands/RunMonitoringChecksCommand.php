<?php

namespace App\Console\Commands;

use App\Jobs\RunMonitoringCheckJob;
use App\Models\Website;
use App\Services\FeatureGate\FeatureGateService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class RunMonitoringChecksCommand extends Command
{
    protected $signature = 'monitoring:run';

    protected $description = 'Dispatch continuous monitoring checks for every verified website that is due, based on its plan\'s scan_frequency';

    /**
     * @var array<string, int>
     */
    private const FREQUENCY_MINUTES = [
        '5min' => 5,
        '15min' => 15,
        '30min' => 30,
        '1hr' => 60,
        '6hr' => 360,
        'daily' => 1440,
    ];

    public function handle(FeatureGateService $featureGate): int
    {
        $dispatched = 0;

        Website::query()
            ->where('status', 'verified')
            ->with('team.subscription.plan')
            ->chunkById(100, function ($websites) use ($featureGate, &$dispatched) {
                foreach ($websites as $website) {
                    if (! $featureGate->can($website, 'monitoring.core')) {
                        continue;
                    }

                    if ($this->isDue($website, $featureGate)) {
                        RunMonitoringCheckJob::dispatch($website->id);
                        $dispatched++;
                    }
                }
            });

        $this->info("Dispatched monitoring checks for {$dispatched} website(s).");

        return self::SUCCESS;
    }

    private function isDue(Website $website, FeatureGateService $featureGate): bool
    {
        $plan = $featureGate->planFor($website->team);
        $intervalMinutes = self::FREQUENCY_MINUTES[$plan?->scan_frequency] ?? 1440;

        $lastCheck = $website->monitoringLogs()->where('check_type', 'uptime')->latest()->value('created_at');

        if (! $lastCheck) {
            return true;
        }

        return Carbon::parse($lastCheck)->addMinutes($intervalMinutes)->isPast();
    }
}
