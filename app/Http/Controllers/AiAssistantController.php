<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Services\AI\AiAssistantService;
use App\Services\FeatureGate\FeatureGateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AiAssistantController extends Controller
{
    public function __construct(
        private readonly AiAssistantService $assistant,
        private readonly FeatureGateService $featureGate,
    ) {}

    public function show(Website $website): Response
    {
        return Inertia::render('AiAssistant/Show', [
            'website' => $website,
            'available' => $website->isVerified() && $this->featureGate->can($website, 'ai.assistant'),
            'messages' => $website->aiMessages()->latest()->limit(50)->get()->reverse()->values(),
            'recentReports' => [
                'performance' => $website->performanceReports()->latest()->limit(3)->get(['id', 'score', 'created_at']),
                'seo' => $website->seoReports()->latest()->limit(3)->get(['id', 'score', 'created_at']),
                'security' => $website->securityReports()->latest()->limit(3)->get(['id', 'score', 'created_at']),
                'accessibility' => $website->accessibilityReports()->latest()->limit(3)->get(['id', 'score', 'created_at']),
            ],
        ]);
    }

    public function store(Request $request, Website $website): RedirectResponse
    {
        if (! $this->featureGate->can($website, 'ai.assistant')) {
            abort(403, 'The AI Assistant is not included in your current plan.');
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
        ]);

        $this->assistant->ask($website, $request->user(), $validated['message']);

        return back();
    }
}
