<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Services\Billing\PlanEnforcementService;
use App\Services\Verification\WebsiteVerificationManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class WebsiteController extends Controller
{
    public function __construct(
        private readonly WebsiteVerificationManager $verificationManager,
        private readonly PlanEnforcementService $planEnforcement,
    ) {}

    public function index(Request $request): Response
    {
        $team = $request->user()->currentTeam;

        $websites = $team->websites()
            ->with('verifications')
            ->latest()
            ->get();

        return Inertia::render('Websites/Index', [
            'websites' => $websites,
            'remainingSlots' => $this->planEnforcement->remainingWebsiteSlots($team),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Websites/Create', [
            'remainingSlots' => $this->planEnforcement->remainingWebsiteSlots($request->user()->currentTeam),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $team = $request->user()->currentTeam;

        if (! $this->planEnforcement->canAddWebsite($team)) {
            throw ValidationException::withMessages([
                'url' => 'You have reached your plan\'s website limit. Upgrade your plan to add more sites.',
            ]);
        }

        $validated = $request->validate([
            'url' => ['required', 'url', 'max:2048'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $domain = $this->normalizeDomain($validated['url']);

        if ($team->websites()->where('domain', $domain)->exists()) {
            throw ValidationException::withMessages([
                'url' => 'This domain has already been added by your team.',
            ]);
        }

        $website = $team->websites()->create([
            'created_by' => $request->user()->id,
            'name' => $validated['name'] ?? $domain,
            'url' => $validated['url'],
            'domain' => $domain,
            'status' => 'pending_verification',
        ]);

        $this->verificationManager->provisionAll($website);

        return redirect()
            ->route('websites.show', $website)
            ->with('success', 'Website added. Verify it to unlock monitoring and scans.');
    }

    public function show(Website $website): Response
    {
        return Inertia::render('Websites/Show', [
            'website' => $website->load('verifications'),
            'verificationMethods' => collect($this->verificationManager->availableMethods())
                ->mapWithKeys(function (string $method) use ($website) {
                    $verification = $website->verifications->firstWhere('method', $method)
                        ?? $this->verificationManager->serviceFor($method)->provision($website);

                    return [$method => $this->verificationManager->serviceFor($method)->instructions($verification) + [
                        'status' => $verification->status,
                        'attempts' => $verification->attempts,
                        'last_error' => $verification->last_error,
                    ]];
                }),
        ]);
    }

    public function verifyNow(Request $request, Website $website): RedirectResponse
    {
        $validated = $request->validate([
            'method' => ['required', 'in:dns_txt,html_file,meta_tag'],
        ]);

        $verification = $this->verificationManager->attempt($website, $validated['method']);

        if ($website->fresh()->isVerified()) {
            return back()->with('success', 'Website verified! Monitoring and scans are now unlocked.');
        }

        return back()->with('error', $verification->last_error ?? 'Verification failed — please check your setup and try again.');
    }

    public function update(Request $request, Website $website): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'group' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $website->update($validated);

        return back()->with('success', 'Website updated.');
    }

    public function pause(Website $website): RedirectResponse
    {
        $website->update(['status' => 'paused', 'paused_at' => now()]);

        return back()->with('success', 'Monitoring paused for this website.');
    }

    public function resume(Website $website): RedirectResponse
    {
        $website->update([
            'status' => $website->verified_method ? 'verified' : 'pending_verification',
            'paused_at' => null,
        ]);

        return back()->with('success', 'Monitoring resumed for this website.');
    }

    public function destroy(Website $website): RedirectResponse
    {
        $website->delete();

        return redirect()->route('websites.index')->with('success', 'Website removed.');
    }

    private function normalizeDomain(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST) ?? $url;

        return strtolower(preg_replace('/^www\./', '', $host));
    }
}
