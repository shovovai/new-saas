<?php

namespace App\Services\Scanning;

use App\Models\PerformanceReport;
use App\Models\Website;

interface PerformanceScanServiceInterface
{
    public function run(Website $website): PerformanceReport;
}
