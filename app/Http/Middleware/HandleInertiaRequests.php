<?php

namespace App\Http\Middleware;

use App\Models\Announcement;
use App\Services\FeatureGate\FeatureGateService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $team = $user?->currentTeam;

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
            ],
            'team' => $team ? [
                'id' => $team->id,
                'name' => $team->name,
                'role' => $team->roleFor($user),
            ] : null,
            'plan' => $team ? app(FeatureGateService::class)->planFor($team)?->only(['id', 'name', 'slug']) : null,
            'enabledFeatures' => $team ? app(FeatureGateService::class)->enabledFeatureKeysForTeam($team) : [],
            'websites' => $team
                ? $team->websites()->orderBy('name')->get(['id', 'name', 'domain', 'status'])
                : [],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'plaintextKey' => fn () => $request->session()->get('plaintextKey'),
            ],
            'announcement' => fn () => $user
                ? Announcement::query()->where('is_active', true)->latest()->get()
                    ->first(fn (Announcement $a) => $a->isCurrentlyLive())?->only(['id', 'title', 'body', 'severity'])
                : null,
        ];
    }
}
