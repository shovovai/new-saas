<?php

namespace App\Jobs;

use App\Models\Website;
use App\Services\Scanning\SeoScanServiceInterface;
use Illuminate\Database\Eloquent\Model;

class RunSeoScanJob extends ScanJob
{
    protected function featureKey(): string
    {
        return 'seo.scans';
    }

    protected function performScan(Website $website): Model
    {
        return app(SeoScanServiceInterface::class)->run($website);
    }
}
