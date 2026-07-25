<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Real browser-based performance scanning
    |--------------------------------------------------------------------------
    |
    | PerformanceScanService shells out to a small Playwright script that
    | drives a real headless Chromium to measure actual Core Web Vitals
    | (LCP, CLS) and Total Blocking Time (an automated-lab proxy for INP —
    | INP itself requires a real user interaction and cannot be measured in
    | a scripted, non-interactive scan; this is the same limitation every
    | lab tool, including Lighthouse, has).
    |
    */
    'node_binary' => env('SCANNING_NODE_BINARY', 'node'),
    'performance_script' => base_path('scanning-scripts/performance-scan.mjs'),
    'chromium_executable' => env('PLAYWRIGHT_CHROMIUM_PATH'),
    'timeout_seconds' => (int) env('SCANNING_TIMEOUT_SECONDS', 30),
];
