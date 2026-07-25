<?php

namespace App\Services\Scanning;

use App\Models\AccessibilityReport;
use App\Models\Website;

interface AccessibilityScanServiceInterface
{
    public function run(Website $website): AccessibilityReport;
}
