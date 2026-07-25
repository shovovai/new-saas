<?php

namespace App\Jobs;

use App\Models\Website;
use App\Services\Scanning\AccessibilityScanServiceInterface;
use Illuminate\Database\Eloquent\Model;

class RunAccessibilityScanJob extends ScanJob
{
    protected function featureKey(): string
    {
        return 'accessibility.scans';
    }

    protected function performScan(Website $website): Model
    {
        return app(AccessibilityScanServiceInterface::class)->run($website);
    }
}
