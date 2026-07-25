<?php

namespace App\Services\Scanning;

use App\Models\SeoReport;
use App\Models\Website;

interface SeoScanServiceInterface
{
    public function run(Website $website): SeoReport;
}
