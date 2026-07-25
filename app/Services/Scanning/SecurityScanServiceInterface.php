<?php

namespace App\Services\Scanning;

use App\Models\SecurityReport;
use App\Models\Website;

interface SecurityScanServiceInterface
{
    public function run(Website $website): SecurityReport;
}
