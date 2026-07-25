<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Services\FeatureGate\FeatureGateService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private readonly FeatureGateService $featureGate) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $team = $user->currentTeam;

        $websites = $team
            ? $team->websites()->latest()->get()
            : collect();

        return Inertia::render('Dashboard', [
            'websites' => $websites->map(fn (Website $website) => [
                'id' => $website->id,
                'name' => $website->name,
                'domain' => $website->domain,
                'status' => $website->status,
                'verified' => $website->isVerified(),
                'scores' => $website->latestScores,
            ]),
            'enabledFeatures' => $team ? $this->featureGate->enabledFeatureKeysForTeam($team) : [],
            'plan' => $team ? $this->featureGate->planFor($team)?->only(['name', 'slug']) : null,
        ]);
    }
}
