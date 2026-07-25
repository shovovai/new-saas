<?php

namespace App\Jobs;

use App\Models\MonitoringJob;
use App\Models\Website;
use App\Services\FeatureGate\FeatureGateService;
use App\Services\Notifications\AlertDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Every scan job re-checks BOTH gates at execution time, not just at
 * dispatch time (Architecture §7) — a site can be un-verified or a plan
 * can change in the time a job sits in the queue. The hard verification
 * gate and the soft feature gate are checked separately and never merged,
 * mirroring EnsureWebsiteVerified + FeatureGateService for HTTP requests.
 */
abstract class ScanJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $websiteId,
        public readonly int $monitoringJobId,
    ) {}

    abstract protected function featureKey(): string;

    abstract protected function performScan(Website $website): Model;

    public function handle(FeatureGateService $featureGate, AlertDispatcher $alerts): void
    {
        $monitoringJob = MonitoringJob::find($this->monitoringJobId);

        if (! $monitoringJob) {
            return;
        }

        $monitoringJob->update(['status' => 'running', 'started_at' => now()]);

        $website = Website::find($this->websiteId);

        if (! $website) {
            $monitoringJob->update(['status' => 'failed', 'failure_reason' => 'Website not found.']);

            return;
        }

        if (! $website->isVerified()) {
            $monitoringJob->update(['status' => 'failed', 'failure_reason' => 'Website is not verified.']);

            return;
        }

        if (! $featureGate->can($website, $this->featureKey())) {
            $monitoringJob->update(['status' => 'failed', 'failure_reason' => 'This scan is not included in the team\'s current plan.']);

            return;
        }

        try {
            $this->performScan($website);
            $monitoringJob->update(['status' => 'completed', 'completed_at' => now()]);
            $alerts->notify($website, 'scan_completed', ucfirst($monitoringJob->type).' scan completed', "The {$monitoringJob->type} scan for {$website->domain} finished.");
        } catch (\Throwable $e) {
            $monitoringJob->update(['status' => 'failed', 'failure_reason' => $e->getMessage()]);
            $alerts->notify($website, 'scan_failed', ucfirst($monitoringJob->type).' scan failed', "The {$monitoringJob->type} scan for {$website->domain} failed: {$e->getMessage()}");
        }
    }
}
