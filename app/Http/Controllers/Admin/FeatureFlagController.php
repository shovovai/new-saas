<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeatureFlag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Global platform-wide kill switches (UIUX §7) — visually and structurally
 * separate from the per-plan feature matrix to avoid confusing "off for
 * everyone" with "off for this plan".
 */
class FeatureFlagController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/FeatureFlags/Index', [
            'flags' => FeatureFlag::query()->orderBy('key')->get(),
        ]);
    }

    public function update(Request $request, FeatureFlag $featureFlag): RedirectResponse
    {
        $validated = $request->validate(['enabled' => ['required', 'boolean']]);

        $featureFlag->update($validated);

        return back()->with('success', "{$featureFlag->label} is now ".($validated['enabled'] ? 'enabled' : 'disabled').' platform-wide.');
    }
}
