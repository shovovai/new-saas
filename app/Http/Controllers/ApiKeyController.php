<?php

namespace App\Http\Controllers;

use App\Models\ApiKey;
use App\Services\FeatureGate\FeatureGateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApiKeyController extends Controller
{
    public function __construct(private readonly FeatureGateService $featureGate) {}

    public function index(Request $request): Response
    {
        $team = $request->user()->currentTeam;

        return Inertia::render('Api/Index', [
            'available' => $this->featureGate->canForTeam($team, 'api.access'),
            'keys' => $team->apiKeys()->latest()->get(['id', 'name', 'key_prefix', 'last_used_at', 'revoked_at', 'created_at']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $team = $request->user()->currentTeam;

        if (! $this->featureGate->canForTeam($team, 'api.access')) {
            abort(403, 'API access is not included in your current plan.');
        }

        if (! $request->user()->hasPermissionTo('api.manage')) {
            abort(403);
        }

        $validated = $request->validate(['name' => ['required', 'string', 'max:255']]);

        $result = ApiKey::generate($team, $request->user(), $validated['name']);

        return back()->with('success', 'API key created.')->with('plaintextKey', $result['plaintext']);
    }

    public function destroy(Request $request, ApiKey $apiKey): RedirectResponse
    {
        if ($apiKey->team_id !== $request->user()->current_team_id) {
            abort(404);
        }

        $apiKey->update(['revoked_at' => now()]);

        return back()->with('success', 'API key revoked.');
    }
}
