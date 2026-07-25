<?php

namespace App\Jobs;

use App\Models\Website;
use App\Services\Scanning\SecurityScanServiceInterface;
use Illuminate\Database\Eloquent\Model;

class RunSecurityScanJob extends ScanJob
{
    protected function featureKey(): string
    {
        return 'security.scans';
    }

    protected function performScan(Website $website): Model
    {
        return app(SecurityScanServiceInterface::class)->run($website);
    }
}
