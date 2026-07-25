<?php

namespace App\Jobs;

use App\Models\Website;
use App\Services\Scanning\PerformanceScanServiceInterface;
use Illuminate\Database\Eloquent\Model;

class RunPerformanceScanJob extends ScanJob
{
    protected function featureKey(): string
    {
        return 'performance.scans';
    }

    protected function performScan(Website $website): Model
    {
        return app(PerformanceScanServiceInterface::class)->run($website);
    }
}
