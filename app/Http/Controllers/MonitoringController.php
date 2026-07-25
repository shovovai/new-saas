<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Services\FeatureGate\FeatureGateService;
use Inertia\Inertia;
use Inertia\Response;

class MonitoringController extends Controller
{
    public function __construct(private readonly FeatureGateService $featureGate) {}

    public function show(Website $website): Response
    {
        $available = $website->isVerified() && $this->featureGate->can($website, 'monitoring.core');

        $logs = $available
            ? $website->monitoringLogs()
                ->whereIn('check_type', ['uptime', 'ssl_expiry', 'domain_expiry', 'dns_change'])
                ->latest()
                ->limit(100)
                ->get()
            : collect();

        return Inertia::render('Monitoring/Show', [
            'website' => $website,
            'available' => $available,
            'logs' => $logs,
            'latestUptime' => $available ? $website->monitoringLogs()->where('check_type', 'uptime')->latest()->first() : null,
            'latestSsl' => $available ? $website->monitoringLogs()->where('check_type', 'ssl_expiry')->latest()->first() : null,
            'latestDomain' => $available ? $website->monitoringLogs()->where('check_type', 'domain_expiry')->latest()->first() : null,
        ]);
    }
}
